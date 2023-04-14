<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\ConvertHelper;
use AppUtils\Interface_Stringable;
use Mailcode\Mailcode;
use Mailcode\Mailcode_Parser_Safeguard;
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

    private bool $mailcode = false;
    private ?string $mailcodeRegex = null;

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

    public function expectMailcode() : self
    {
        $this->mailcode = true;
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

    /**
     * @return string[]
     */
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
            $this->debug('------------------------------------------------------');
            $this->debug('Find prices');
            $this->debug('- Analysing text with a length of [%s] characters.', strlen($subject));
            $this->debug('- Using regex: %s', $regex);
            $this->debug(
                '- Working with [%s] expected currencies: [%s].',
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

        $this->debug('- Regex found [%s] matches.', count($matches));

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
     * @param string[] $chars
     * @return string
     */
    private function detectWhitespaceFront(array $chars) : string
    {
        $space = '';

        foreach ($chars as $char)
        {
            if(!ctype_space($char)) {
                return $space;
            }

            $space .= $char;
        }

        return $space;
    }

    /**
     * @param string[] $chars
     * @return string
     */
    private function detectWhitespaceEnd(array $chars) : string
    {
        $spaces = array();

        while(true)
        {
            $char = array_pop($chars);

            if(ctype_space($char)) {
                $spaces[] = $char;
                continue;
            }

            break;
        }

        $spaces = array_reverse($spaces);

        return implode('', $spaces);
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
        $matchedText = str_replace(' ', ' ', $matchedText);

        $this->debug('Match [#%s] | Parsing match [%s]', $matchNumber, $matchedText);

        $chars = ConvertHelper::string2array($matchedText);
        $spaceFront = $this->detectWhitespaceFront($chars);
        $spaceEnd = $this->detectWhitespaceEnd($chars);

        $price = trim($matchedText);

        if(preg_match($this->compilePriceRegex(), $price, $result) === false) {
            $this->debug('Match [#%s] | Ignoring, regex returned false.', $matchNumber);
            return null;
        }

        if(empty($result)) {
            $this->debug('Match [#%s] | Ignoring, regex result array is empty.', $matchNumber);
            return null;
        }

        // To facilitate finding the filled positions in the matches
        $result = $this->nullifyEmpty($result);
        $numberString = (string)$result[5];

        if(!preg_match('/\d/', $numberString)) {
            $this->debug('Match [#%s] | Ignoring, no numeric data found.', $matchNumber);
            return null;
        }

        if($this->mailcode === true && $this->isMailcodePlaceholder($numberString)) {
            $this->debug('Match [#%s] | Ignoring, is a Mailcode placeholder.', $matchNumber);
            return null;
        }

        $sign = $result[1] ?? $result[3] ?? '';
        $currencySymbol = $result[2] ?? $result[4] ?? $result[7] ?? '';
        $number = $this->parseNumber($numberString, $result[6]);
        $vat = $result[8] ?? '';

        if($this->debug) {
            print_r([
                'currency' => $currencySymbol
            ]);
        }

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

    private function isMailcodePlaceholder(string $subject) : bool
    {
        if(!isset($this->mailcodeRegex))
        {
            // The Mailcode placeholders have a syntax like this:
            // {DELIMITER}{NUMBERS X 10}{DELIMITER}
            $this->mailcodeRegex = sprintf(
                '/%1$s[0-9]{10}%1$s/',
                preg_quote( Mailcode::create()->createSafeguard('')->getDelimiter(), '/')
            );
        }

        $result = preg_match($this->mailcodeRegex, $subject);

        return $result !== false && $result > 0;
    }

    /**
     * Replaces all whitespace values with NULL in the array.
     *
     * @param array<int,string> $values
     * @return array<int,string|NULL>
     */
    private function nullifyEmpty(array $values) : array
    {
        foreach($values as $key => $value)
        {
            if(empty(trim((string)$value))) {
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
     * @return array{number:int,decimals:string,_normalized:string}
     */
    private function parseNumber(string $number, ?string $specialDecimals) : array
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
                '_normalized' => $normalized
            );
        }

        // Decimals are in fact thousands in a large number like 100,000
        if(strlen($decimals) === 3) {
            $parts[] = $decimals;
            return array(
                'number' => (int)implode('', $parts),
                'decimals' => '',
                '_normalized' => $normalized
            );
        }

        return array(
            'number' => (int)implode('', $parts),
            'decimals' => $decimals,
            '_normalized' => $normalized
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
            '{SPACE}'.
            // 3. Minus sign, if present
            '(-?)'.
            // 4. Prefix space #2
            '{SPACE}'.
            // 5. Currency symbol after minus sign, e.g. "-$40"
            '{SYMBOLS_AFTER_MINUS}'.
            // 6. Prefix space #3
            '{SPACE}'.
            // 7. Number with spaces, commas or dots
            '([\d,.  ]+)?'.
            // 8. Hyphen decimals
            '('.
                '-'.
                '|'.
                '–'. // EN dash
                '|'.
                '&#8211;'.
            ')?'.
            '{SPACE}'.
            // 9. Currency symbol at the end, e.g. "40$"
            '{SYMBOLS_END}'.
            // 10. Suffix space
            '{SPACE}'.
            // 11. French VAT, if present, e.g. "40€ TTC"
            '(TTC|HT)?';

        $space = '[\s ]*';
        $optional = $space;
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
            $entry['{SPACE}'] = $space;

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
            '/(-?)\s*(%1$s)?\s*(-?)\s*(%1$s)?\s*([0-9,. ]+)\s*(-?)\s*(%1$s)?\s*(TTC|HT)?/i',
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

            // Fix for false "Euro" positives: this way of writing the currency
            // name is too open to interpretation to be used for currency formatting,
            // so we add it as a pseudo symbol. This way it is detected but ignored,
            // because it is not a known currency symbol name.
            $name = $currency->getName();
            if($name === 'EUR') {
                $names[] = 'Euro';
            }

            $names[] = $name;
            $entities[] = $currency->getHTMLEntity();
        }

        return implode('|', array_merge($symbols, $names, $entities));
    }

    /**
     * @param string $string
     * @param int|string|Interface_Stringable|NULL ...$params
     * @return void
     */
    private function debug(string $string, ...$params) : void
    {
        if(!$this->debug) {
            return;
        }

        echo sprintf($string, ...$params).PHP_EOL;
    }
}
