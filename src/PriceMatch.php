<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

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

    public function __construct(string $matchedString, BaseCurrency $currency, int $number, string $decimals, string $sign, string $spaceFront='', string $spaceEnd='', string $vat='')
    {
        $this->matchedString = $matchedString;
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

    public function getNumber() : int
    {
        return $this->number;
    }

    public function getDecimals(): string
    {
        return $this->decimals;
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

    public function format(string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator) : PriceFormatter
    {
        return new PriceFormatter($this, $decimalSeparator, $thousandsSeparator, $arithmeticSeparator);
    }
}
