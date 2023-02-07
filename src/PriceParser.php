<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\ConvertHelper;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class PriceParser
{
    public const ERROR_NO_EXPECTED_CURRENCIES_SET = 128001;

    private Currencies $currencies;
    private bool $debug = false;

    /**
     * @var BaseCurrency[]
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
    public function setDebugEnabled(bool $enabled) : self
    {
        $this->debug = $enabled;
        return $this;
    }

    /**
     * @param string|BaseCurrency $currency
     * @return $this
     */
    public function expectCurrency($currency) : self
    {
        if($currency instanceof BaseCurrency)
        {
            $name = $currency->getName();
        }
        else
        {
            $name = $currency;
        }

        $this->expected[$name] = $this->currencies->getByName($name);
        return $this;
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
     * @param string $subject
     * @param int $limit Maximum amount of prices to return
     * @return PriceMatch[]
     * @throws CurrencyParserException
     * @see self::ERROR_NO_EXPECTED_CURRENCIES_SET
     */
    public function findPrices(string $subject, int $limit=0) : array
    {
        $this->requireCurrencies();

        $this->debug('Analysing text with a length of [%s] characters.', strlen($subject));
        $this->debug('Limit is set to [%s].', $limit);

        $result = array();
        $regex = $this->compileRegex();
        preg_match_all(
            $regex,
            $subject,
            $result,
            PREG_PATTERN_ORDER
        );

        $prices = array();
        $amount = 0;

        foreach( $result[0] as $idx => $match )
        {
            // Empty matches can happen because of how the regex is built,
            // with the optional capturing groups.
            if(trim($match) === '') {
                $this->debug('Match [#%s] | Empty, skipping.', $idx);
                continue;
            }

            $this->debug('Match [#%s] | Matched text: [%s].', $idx, $match);

            if($limit > 0 && $amount >= $limit ) {
                $this->debug('Match [#%s] | Limit of [%s] reached, stopping.', $idx, $limit);
                break;
            }

            // Extract the information from the capturing
            // groups in the regex matches.
            $symbol1 = $result[1][$idx];
            $space1 = $result[2][$idx];
            $sign = trim($result[3][$idx]);
            $space2 = $result[4][$idx];
            $symbol2 = $result[5][$idx];
            $space3 = $result[6][$idx];
            $number = $this->parseNumber($result[7][$idx], $result[8][$idx]);
            $symbol3 = $result[9][$idx];
            $space4 = $result[10][$idx];
            $vat = $result[11][$idx];

            // Determine the currency symbol to use, depending
            // on which capturing group was filled in the regex
            // (as they are all optional).
            $currencySymbol = '';
            if(!empty($symbol1)) { $currencySymbol = $symbol1; }
            if(!empty($symbol2)) { $currencySymbol = $symbol2; }
            if(!empty($symbol3)) { $currencySymbol = $symbol3; }

            // No currency found? Ignore this number - It means
            // that none of the capturing groups for currency
            // symbols were filled, which means this is not a price.
            if(empty($currencySymbol))
            {
                $this->debug('Match [#%s] | No currency symbol detected, skipping.', $idx);
                continue;
            }

            $currencyInstance = $this->currencies->autoDetect($currencySymbol, $this->expected);

            // None of the currencies we were expecting match this
            // currency symbol: In theory, this cannot happen because
            // the regex only matches the expected currencies - but
            // this way the static code analysers are happy.
            if($currencyInstance === null)
            {
                $this->debug('Match [#%s] | No currency instance found for symbol [%s].', $idx, $currencySymbol);
                continue;
            }

            // Remove irrelevant spaces to keep the right
            // whitespace at the front of the price,
            // depending on which optional capturing groups
            // were filled in the regex matches.
            if(!empty($symbol2)) { $space3 = ''; }
            if(!empty($sign)) { $space2 = ''; }
            if(!empty($symbol1)) { $space1 = ''; }

            // Determine which of the spaces to keep in front
            // of the number, if any. We do this in reverse
            // order to keep the frontmost space that is not
            // empty.
            $spaceFront = '';
            if(!empty($space3)) { $spaceFront = $space3; }
            if(!empty($space2)) { $spaceFront = $space2; }
            if(!empty($space1)) { $spaceFront = $space1; }

            $spaceEnd = $space4;
            if(!empty($vat))
            {
                $spaceEnd = '';
            }

            if($this->debug)
            {
                $this->debug('Match [#%s] | Detected a valid match. Details follow:', $idx);

                echo print_r(array(
                    '1. symbol #1' => $symbol1,
                    '2. space #1' => ConvertHelper::hidden2visible($space1),
                    '3. sign' => $sign,
                    '4. space #2' => ConvertHelper::hidden2visible($space2),
                    '5. symbol #2' => $symbol2,
                    '6. space #3' => ConvertHelper::hidden2visible($space3),
                    '7. price' => $number['number'],
                    '8. decimals' => $number['decimals'],
                    '9. symbol #3' => $symbol3,
                    '10. space #4' => ConvertHelper::hidden2visible($space4),
                    '11. vat' => $vat,

                    '_regex' => $regex,
                    '_match' => $match,
                    '_currency' => $currencySymbol,
                    '_spaceFront' => ConvertHelper::hidden2visible($spaceFront),
                    '_spaceEnd' => ConvertHelper::hidden2visible($spaceEnd),
                    '_numberInfo' => $number
                ), true);
            }

            $prices[] = new PriceMatch(
                $match,
                $currencyInstance,
                $number['number'],
                $number['decimals'],
                $sign,
                $spaceFront,
                $spaceEnd,
                strtoupper($vat)
            );

            $amount++;
        }

        return $prices;
    }

    /**
     * @param string $subject
     * @return PriceMatch|null
     * @throws CurrencyParserException
     */
    public function findFirstPrice(string $subject) : ?PriceMatch
    {
        $matches = $this->findPrices($subject, 1);

        if(!empty($matches)) {
            return array_shift($matches);
        }

        return null;
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
     * @param string $specialDecimals
     * @return array{number:int,decimals:string,_normalized:string,_parts:array<int,string>}
     */
    private function parseNumber(string $number, string $specialDecimals) : ?array
    {
        $normalized = str_replace(array(',', '.', ' '), '_', trim($number));
        $parts = explode('_', $normalized);

        // Decimals is a hyphen (german style)
        if(!empty($specialDecimals))
        {
            return array(
                'number' => (int)implode('', $parts),
                'decimals' => '-',
                '_normalized' => $normalized,
                '_parts' => $parts
            );
        }

        $decimals = array_pop($parts);

        // Number without decimals, e.g. 50
        if(empty($parts)) {
            return array(
                'number' => (int)$decimals,
                'decimals' => '',
                'normalized' => $normalized,
                'parts' => $parts
            );
        }

        // Decimals are in fact thousands in a large number like 100,000
        if(strlen($decimals) === 3) {
            $parts[] = $decimals;
            return array(
                'number' => (int)implode('', $parts),
                'decimals' => '',
                'normalized' => $normalized,
                'parts' => $parts
            );
        }

        return array(
            'number' => (int)implode('', $parts),
            'decimals' => $decimals,
            'normalized' => $normalized,
            'parts' => $parts
        );
    }

    /**
     * Creates the regex used by {@see Newsletter_CharFilter_PriceNotation::formatPrices()}
     * to detect prices in the HTML code.
     *
     * @return string
     */
    private function compileRegex() : string
    {
        $symbols = $this->compileSymbolRegex();

        return
            '/'.
            // 1. Currency symbol on front, e.g. "$40"
            '('.$symbols.')?'.
            // 2 . Prefix space #1
            '(\s*)'.
            // 3. Minus sign, if present
            '(-?)'.
            // 4. Prefix space #2
            '(\s*)'.
            // 5. Currency symbol after minus sign, e.g. "-$40"
            '('.$symbols.')?'.
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
            '('.$symbols.')?'.
            // 10. Suffix space
            '(\s*)'.
            // 11. French VAT, if present, e.g. "40€ TTC"
            '(TTC|HT)?'.
            '/iu';
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

        foreach($this->expected as $currency)
        {
            $symbol = $currency->getSymbol();

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
