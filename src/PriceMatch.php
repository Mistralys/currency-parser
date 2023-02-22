<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\CurrencyParser\Formatter\IndividualCustomFormatter;
use Mistralys\CurrencyParser\Formatter\IndividualLocaleFormatter;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class PriceMatch
{
    private BaseCurrencyLocale $locale;
    private int $number;
    private string $decimals;
    private string $sign;
    private string $vat;
    private string $spaceFront;
    private string $spaceEnd;
    private string $matchedString;
    private string $currencySymbol;

    public function __construct(string $matchedString, $currencySymbol, BaseCurrencyLocale $locale, int $number, string $decimals, string $sign, string $spaceFront='', string $spaceEnd='', string $vat='')
    {
        $this->matchedString = $matchedString;
        $this->currencySymbol = $currencySymbol;
        $this->locale = $locale;
        $this->number = $number;
        $this->decimals = $decimals;
        $this->sign = $sign;
        $this->vat = $vat;
        $this->spaceFront = $spaceFront;
        $this->spaceEnd = $spaceEnd;
    }

    public function getCurrency(): BaseCurrency
    {
        return $this->locale->getCurrency();
    }

    public function getLocale() : BaseCurrencyLocale
    {
        return $this->locale;
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

    // region: Helper methods

    /**
     * Formats the price with text-based non-breaking spaces.
     *
     * @return string
     * @throws CurrencyParserException
     */
    public function formatText() : string
    {
        return $this->createFormatter()
            ->setNonBreakingSpaceText()
            ->format();
    }

    /**
     * Formats the price
     * @return string
     * @throws CurrencyParserException
     */
    public function formatHTML() : string
    {
        return $this->createFormatter()
            ->setNonBreakingSpaceHTML()
            ->format($this);
    }

    /**
     * Creates a formatter instance pre-configured for
     * the price's currency locale.
     *
     * @return IndividualLocaleFormatter
     */
    public function createFormatter() : IndividualLocaleFormatter
    {
        return new IndividualLocaleFormatter($this);
    }

    /**
     * Creates a customisable formatter for the price.
     *
     * @return IndividualCustomFormatter
     */
    public function createCustomFormatter() : IndividualCustomFormatter
    {
        return new IndividualCustomFormatter($this);
    }

    public function createFilter() : PriceFilter
    {
        return PriceFilter::createForLocales($this->locale);
    }

    // endregion
}
