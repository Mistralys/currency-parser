<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class PriceMatch
{
    private BaseCurrency $currency;
    private int $number;
    private string $decimals;
    private string $sign;
    private string $vat;
    private string $spaceFront;
    private string $spaceEnd;
    private string $matchedString;
    private string $currencySymbol;

    public function __construct(string $matchedString, $currencySymbol, BaseCurrency $currency, int $number, string $decimals, string $sign, string $spaceFront='', string $spaceEnd='', string $vat='')
    {
        $this->matchedString = $matchedString;
        $this->currencySymbol = $currencySymbol;
        $this->currency = $currency;
        $this->number = $number;
        $this->decimals = $decimals;
        $this->sign = $sign;
        $this->vat = $vat;
        $this->spaceFront = $spaceFront;
        $this->spaceEnd = $spaceEnd;
    }

    public function getCurrency(): BaseCurrency
    {
        return $this->currency;
    }

    public function getCurrencyName() : string
    {
        return $this->getCurrency()->getName();
    }

    public function getMatchedCurrencySymbol() : string
    {
        return $this->currencySymbol;
    }

    public function getNumber() : int
    {
        return $this->number;
    }

    /**
     * The decimals can be either a number, or
     * the german short notation hyphen.
     *
     * @return string
     */
    public function getDecimals(): string
    {
        return $this->decimals;
    }

    public function getDecimalsInt() : int
    {
        $decimals = $this->getDecimals();

        if(is_numeric($decimals)) {
            return (int)$decimals;
        }

        return 0;
    }

    public function hasDecimals() : bool
    {
        return !empty($this->decimals);
    }

    public function getAsFloat() : float
    {
        $decimals = $this->getDecimals();
        $number = $this->getNumber();

        if($decimals === '-') {
            $decimals = 0;
        }

        $multiplier = 1;
        if($this->isNegative()) {
            $multiplier = -1;
        }

        return ((float)($number.'.'.$decimals)) * $multiplier;
    }

    /**
     * Gets the price in money integer notation, to
     * use with the Money library for example.
     *
     * Examples:
     *
     * <pre>
     *  5.00 => 500
     * 50.00 => 5000
     * </pre>
     *
     * @return int
     * @link https://github.com/moneyphp/money
     */
    public function getAsMoney() : int
    {
        return (int)sprintf(
            '%d%02d',
            $this->getNumber(),
            $this->getDecimalsInt()
        );
    }

    public function isNegative() : bool
    {
        return $this->getSign() !== '';
    }

    public function getSign(): string
    {
        return $this->sign;
    }

    public function getVAT(): string
    {
        return $this->vat;
    }

     public function hasVAT() : bool
     {
         return $this->vat !== '';
     }

    public function getSpaceFront(): string
    {
        return $this->spaceFront;
    }

    public function getSpaceEnd(): string
    {
        return $this->spaceEnd;
    }

    public function getMatchedString(): string
    {
        return $this->matchedString;
    }

    /**
     * Formats the price using the currency's default locale.
     *
     * @param string|BaseCurrencyLocale|NULL $localeNameOrInstance
     * @return string
     * @throws CurrencyParserException
     */
    public function format($localeNameOrInstance=null) : string
    {
        if($localeNameOrInstance === null) {
            $localeNameOrInstance = $this->currency->getDefaultLocale();
        }

        return currencyLocale($localeNameOrInstance)->formatPrice($this);
    }

    public function createFilter($localeNameOrInstance=null) : PriceFilter
    {
        if($localeNameOrInstance === null) {
            $localeNameOrInstance = $this->currency->getDefaultLocale();
        }

        return PriceFilter::createForLocales($localeNameOrInstance);
    }
}
