<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\ConvertHelper;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class PriceParser
{
    public const ERROR_NO_EXPECTED_CURRENCIES_SET = 128001;
    public const ERROR_DEFAULT_CURRENCY_SYMBOL_MISMATCH = 128002;

    private Currencies $currencies;
    private bool $debug = false;
    private ?string $masterRegex = null;
    private ?string $priceRegex = null;
    /**
     * @var array<string,BaseCurrencyLocale>
     */
    private array $expected = array();

    private function __construct()
    {
        $this->currencies = Currencies::getInstance();
    }

    public static function create() : PriceParser
    {
        return new PriceParser();
    }

    /**
     * Parses a single price string.
     *
     * @param string $price The price string to parse, e.g. "$50"
     * @param string|BaseCurrencyLocale|NULL $localeNameOrInstance
     * @return PriceMatch|null The price match, or NULL if it could not be detected.
     * @throws CurrencyParserException
     */
    public static function tryParsePrice(string $price, $localeNameOrInstance=null) : ?PriceMatch
    {
        $parser = self::create();

        if(empty($localeNameOrInstance)) {
            $parser->expectAnyCurrency();
        } else {
            $parser->expectCurrency($localeNameOrInstance);
        }

        return $parser
            ->findPrices($price)
            ->getFirst();
    }

    /**
     * @param string $price
     * @param string|BaseCurrencyLocale|NULL $localeNameOrInstance
     * @return PriceMatch
     * @throws CurrencyParserException
     */
    public static function parsePrice(string $price, $localeNameOrInstance=null) : PriceMatch
    {
        $parser = self::create();

        if(empty($localeNameOrInstance)) {
            $parser->expectAnyCurrency();
        } else {
            $parser->expectCurrency($localeNameOrInstance);
        }

        return $parser
            ->findPrices($price)
            ->requireFirst();
    }

    public function setDebugEnabled(bool $enabled) : self
    {
        $this->debug = $enabled;
        return $this;
    }

    /**
     * @param string|BaseCurrencyLocale $nameOrInstance Locale name (EUR_FR), or currency locale instance.
     * @return $this
     * @throws CurrencyParserException
     */
    public function expectCurrency($nameOrInstance) : self
    {
        $locale = $this->currencies->getLocale($nameOrInstance);

        $this->expected[$locale->getCurrencyName()] = $locale;

        $this->resetRegexes();

        return $this;
    }

    /**
     * @param BaseCurrencyLocale|string ...$currencies
     * @return $this
     * @throws CurrencyParserException
     */
    public function expectCurrencies(...$currencies) : self
    {
        foreach($currencies as $currency)
        {
            $this->expectCurrency($currency);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws CurrencyParserException
     */
    public function expectAnyCurrency() : self
    {
        return $this->expectCurrencies(...Currencies::getInstance()->getDefaultLocales());
    }

    /**
     * @var array<string,string>
     */
    private array $symbolDefaults = array(
        '$' => 'USD'
    );

    /**
     * In cases where several countries use the same currency symbol
     * ($ used for USD and CAD for example), this can be used to
     * specify which currency to choose for prices written with that
     * symbol.
     *
     * @param string $symbol The currency symbol, e.g. "$"
     * @param BaseCurrencyLocale|string $nameOrInstance The currency locale instance (or name) to use as default for the symbol (must use the same symbol).
     * @return $this
     * @throws CurrencyParserException
     */
    public function setSymbolDefault(string $symbol, $nameOrInstance) : self
    {
        $collection = Currencies::getInstance();

        $collection->requireSymbolExists($symbol);

        $locale = $collection->getLocale($nameOrInstance);
        $currency = $locale->getCurrency();

        // Also add it to the list of expected currencies.
        $this->expectCurrency($locale);

        if($currency->getSymbol() === $symbol) {
            $this->symbolDefaults[$symbol] = $currency->getName();
            return $this;
        }

        throw new CurrencyParserException(
            'Currency symbol mismatch.',
            sprintf(
                'Cannot set the currency [%s] as default for symbol [%s], its symbol is a different one ([%s]).',
                $currency->getName(),
                $symbol,
                $currency->getSymbol()
            ),
            self::ERROR_DEFAULT_CURRENCY_SYMBOL_MISMATCH
        );
    }

    /**
     * @return void
     * @throws CurrencyParserException
     * @see self::ERROR_NO_EXPECTED_CURRENCIES_SET
     */
    private function requireCurrencies() : void
    {
        if(!empty($this->expected)) {
            return;
        }

        throw new CurrencyParserException(
            'Parser has no expected currencies.',
            sprintf(
                'Expected currencies must be added via [%s] before parsing.',
                array($this, 'expectCurrency')[1]
            ),
            self::ERROR_NO_EXPECTED_CURRENCIES_SET
        );
    }

    protected function getExpectedCurrencyNames() : array
    {
        $result = array();

        foreach($this->expected as $locale)
        {
            $result[] = $locale->getCurrency()->getName();
        }

        return $result;
    }

    /**
     * Detects all unique price snippets in the subject string,
     * and returns a {@see PriceMatches} instance to easily
     * access them.
     *
     * @param string $subject
     * @return PriceMatches
     * @throws CurrencyParserException
     * @see self::ERROR_NO_EXPECTED_CURRENCIES_SET
     */
    public function findPrices(string $subject) : PriceMatches
    {
        $this->requireCurrencies();

        $regex = $this->compileMasterRegex();

        if($this->debug) {
            $this->debug('Analysing text with a length of [%s] characters.', strlen($subject));
            $this->debug('Using regex: %s', $regex);
            $this->debug(
                'Working with [%s] expected currencies: [%s].',
                count($this->expected),
                implode(', ', $this->getExpectedCurrencyNames())
            );
        }

        $result = array();
        preg_match_all(
            $regex,
            $subject,
            $result,
            PREG_PATTERN_ORDER
        );

        $matches = array_unique($result[0]);

        $result = array();
        $matchNumber = 1;
        foreach($matches as $matchedText)
        {
            if(empty(trim($matchedText))) {
                continue;
            }

            $match = $this->parseMatch($matchNumber, $matchedText);

            if($match !== null) {
                $result[] = $match;
                $this->debug('Match [#%s] | Matched text: [%s].', $matchNumber, $matchedText);
                $matchNumber++;
            }
        }

        return new PriceMatches($subject, $result);
    }

    /**
     * Parses a single matched price.
     *
     * @param int $matchNumber
     * @param string $matchedText
     * @return PriceMatch|null
     */
    private function parseMatch(int $matchNumber, string $matchedText) : ?PriceMatch
    {
        preg_match('/(\s*)(.*)(\s*)/', $matchedText, $result);

        $spaceFront = $result[1];
        $price = str_replace(' ', '', $result[2]);
        $spaceEnd = $result[3];

        preg_match($this->compilePriceRegex(), $price, $result);

        // To facilitate finding the filled positions in the matches
        $result = $this->nullifyEmpty($result);

        $sign = $result[1] ?? $result[3] ?? '';
        $currencySymbol = $result[2] ?? $result[4] ?? $result[7] ?? '';
        $number = $this->parseNumber($result[5], $result[6]);
        $vat = $result[8] ?? '';

        // Fix the price name case
        if(ctype_alpha($currencySymbol)) {
            $currencySymbol = strtoupper($currencySymbol);
        }

        $locale = $this->currencies->autoDetect(
            $currencySymbol,
            $this->expected,
            $this->symbolDefaults
        );

        // None of the currencies we were expecting match this
        // currency symbol: In theory, this cannot happen because
        // the regex only matches the expected currencies - but
        // this way the static code analysers are happy.
        if($locale === null)
        {
            $this->debug('Match [#%s] | No currency instance found for symbol [%s].', $matchNumber, $currencySymbol);
            return null;
        }

        if($this->debug) {
            print_r(array(
                'matchedText' => $matchedText,
                'spaceFront' => ConvertHelper::hidden2visible($spaceFront),
                'minus' => $sign,
                'currencySymbol' => $currencySymbol,
                'number' => $number,
                'vat' => $vat,
                'spaceEnd' => ConvertHelper::hidden2visible($spaceEnd),
                'matches' => $result
            ));
        }

        return new PriceMatch(
            $matchedText,
            $currencySymbol,
            $locale,
            $number['number'],
            $number['decimals'],
            $sign,
            $spaceFront,
            $spaceEnd,
            strtoupper($vat)
        );
    }

    /**
     * Replaces all whitespace values with NULL in the array.
     *
     * @param array<string,string> $values
     * @return array<string,string|NULL>
     */
    private function nullifyEmpty(array $values) : array
    {
        foreach($values as $key => $value)
        {
            if(empty(trim($value))) {
                $values[$key] = null;
            }
        }

        return $values;
    }

    /**
     * Parses a number string to detect the decimals and
     * thousands. It does this on the following assumptions:
     *
     * 1) Decimals are always 1-2 numbers long.
     * 2) Decimals may be replaced by a hyphen (german short notation).
     *
     * Commas, dots and spaces can be used interchangeably
     * as thousands separators and decimal separators.
     *
     * Examples:
     *
     * - 1000.00
     * - 1,000.00
     * - 1,000,00
     * - 1 000.00
     * - 1000
     * - 1000,-
     *
     * @param string $number
     * @param string|NULL $specialDecimals
     * @return array{number:int,decimals:string,_normalized:string,_parts:array<int,string>}
     */
    private function parseNumber(string $number, ?string $specialDecimals) : ?array
    {
        $normalized = str_replace(array(',', '.', ' '), '_', trim($number));
        $parts = explode('_', $normalized);

        // Decimals is a hyphen (german style)
        if(!empty($specialDecimals))
        {
            return array(
                'number' => (int)implode('', $parts),
                'decimals' => '-',
                '_normalized' => $normalized
            );
        }

        $decimals = array_pop($parts);

        // Number without decimals, e.g. 50
        if(empty($parts)) {
            return array(
                'number' => (int)$decimals,
                'decimals' => '',
                'normalized' => $normalized
            );
        }

        // Decimals are in fact thousands in a large number like 100,000
        if(strlen($decimals) === 3) {
            $parts[] = $decimals;
            return array(
                'number' => (int)implode('', $parts),
                'decimals' => '',
                'normalized' => $normalized
            );
        }

        return array(
            'number' => (int)implode('', $parts),
            'decimals' => $decimals,
            'normalized' => $normalized
        );
    }

    private function resetRegexes() : void
    {
        $this->masterRegex = null;
        $this->priceRegex = null;
    }

    /**
     * Creates the regex used by {@see Newsletter_CharFilter_PriceNotation::formatPrices()}
     * to detect prices in the HTML code.
     *
     * @return string
     */
    private function compileMasterRegex() : string
    {
        if(isset($this->masterRegex)) {
            return $this->masterRegex;
        }

        // The core regular expression has three places where
        // the currency symbol can be placed. These places are
        // marked with placeholders here, e.g. {SYMBOLS_FRONT}.
        $regexBase =
            // 1. Currency symbol on front, e.g. "$40"
            '{SYMBOLS_FRONT}'.
            // 2 . Prefix space #1
            '(\s*)'.
            // 3. Minus sign, if present
            '(-?)'.
            // 4. Prefix space #2
            '(\s*)'.
            // 5. Currency symbol after minus sign, e.g. "-$40"
            '{SYMBOLS_AFTER_MINUS}'.
            // 6. Prefix space #3
            '(\s*)'.
            // 7. Number with spaces, commas or dots
            '([\d,. ]+)?'.
            // 8. Hyphen decimals
            '('.
                '-'.
                '|'.
                '–'. // EN dash
                '|'.
                '&#8211;'.
            ')?'.
            '\s*'.
            // 9. Currency symbol at the end, e.g. "40$"
            '{SYMBOLS_END}'.
            // 10. Suffix space
            '(\s*)'.
            // 11. French VAT, if present, e.g. "40€ TTC"
            '(TTC|HT)?';

        $optional = '(\s*)';
        $mandatory = '('.$this->compileSymbolRegex().')';

        // Initially, all three symbol locations used optional
        // capturing groups, e.g. "($|€|USD|EUR)?". However, as
        // most of the regex consists of optional groups, this
        // led to many false positives.
        //
        // To solve this problem, the regex is duplicated into
        // three switch cases, each having the currency symbol
        // as mandatory in one of the three possible spots.
        //
        // The optional capturing group used instead of the
        // currency symbols is a neutral whitespace group to
        // guarantee that the group indexes stay the same for
        // all three variants.
        $list = array(
            'Symbol at the front' => array(
                '{SYMBOLS_FRONT}' => $mandatory,
                '{SYMBOLS_AFTER_MINUS}' => $optional,
                '{SYMBOLS_END}' => $optional
            ),
            'Symbol after minus sign' => array(
                '{SYMBOLS_FRONT}' => $optional,
                '{SYMBOLS_AFTER_MINUS}' => $mandatory,
                '{SYMBOLS_END}' => $optional
            ),
            'Symbol at the end' => array(
                '{SYMBOLS_FRONT}' => $optional,
                '{SYMBOLS_AFTER_MINUS}' => $optional,
                '{SYMBOLS_END}' => $mandatory
            )
        );

        // Create the actual regex by replacing the currency symbol
        // places with the intended capturing groups.
        $switches = array();
        foreach($list as $label => $entry)
        {
            $switches[] =
                '(?# '.$label.')'.
                str_replace(
                    array_keys($entry),
                    array_values($entry),
                    $regexBase
                );
        }

        // The outer group to handle the switch case is a
        // non-capturing group, with the (?: notation.
        $regex = '/(?:'.implode('|', $switches).')/iu';

        $this->masterRegex = $regex;

        return $regex;
    }

    private function compilePriceRegex() : string
    {
        if(isset($this->priceRegex)) {
            return $this->priceRegex;
        }

        $regex = sprintf(
             //        1     2     3    4       5        6   7      8
            '/(-?)(%1$s)?(-?)(%1$s)?([0-9,. ]+)(-?)(%1$s)?(TTC|HT)?/i',
            $this->compileSymbolRegex()
        );

        $this->priceRegex = $regex;

        return $regex;
    }

    /**
     * Uses the currencies defined in {@see Currencies}
     * to build a regex switch case to detect any of them
     * by their symbol, name or numbered HTML entity.
     *
     * @return string
     */
    private function compileSymbolRegex() : string
    {
        $symbols = array();
        $names = array();
        $entities = array();

        foreach($this->expected as $locale)
        {
            $currency = $locale->getCurrency();

            $symbol = preg_quote($currency->getSymbol(), '/');

            if(!in_array($symbol, $symbols, true))
            {
                $symbols[] = $symbol;
            }

            $names[] = $currency->getName();
            $entities[] = $currency->getHTMLEntity();
        }

        return implode('|', array_merge($symbols, $names, $entities));
    }

    public function getLogIdentifier(): string
    {
        return 'PriceParser';
    }

    private function debug(string $string, ...$params) : void
    {
        if(!$this->debug) {
            return;
        }

        echo sprintf($string, ...$params).PHP_EOL;
    }
}
