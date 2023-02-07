<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

class PriceFormatter
{
    public const SPACE_PLACEHOLDER = '_space_';
    public const SYMBOL_POSITION_FRONT = 'front';
    public const SYMBOL_POSITION_END = 'end';
    private string $nonBreakingSpace = '&#160;';

    private PriceMatch $price;
    private string $decimalSeparator;
    private string $thousandsSeparator;
    private string $arithmeticSeparator;
    private bool $financial = false;
    private string $symbolPosition = self::SYMBOL_POSITION_FRONT;

    public function __construct(PriceMatch $price, string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator)
    {
        $this->price = $price;
        $this->decimalSeparator = $decimalSeparator;
        $this->thousandsSeparator = $thousandsSeparator;
        $this->arithmeticSeparator = $arithmeticSeparator;
    }

    public function makeFinancial() : self
    {
        $this->financial = true;
        return $this;
    }

    public function setSymbolPosition(string $position) : self
    {
        $this->symbolPosition = $position;
        return $this;
    }

    public function formatR(string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator) : string
    {
        return str_replace(
            PriceFormatter::SPACE_PLACEHOLDER,
            $this->nonBreakingSpace,
            $this->renderRegular($decimalSeparator, $thousandsSeparator, $arithmeticSeparator)
        );
    }

    public function formatFinancial() : string
    {

    }

    private function renderRegular(string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator) : string
    {
        return $this->spaceFront.
            $this->renderSign($arithmeticSeparator).
            $this->renderNumber($this->number, $thousandsSeparator).
            $this->renderDecimals($decimalSeparator).
            $this->renderCurrency().
            $this->renderVAT().
            $this->spaceEnd;
    }

    private function renderFinancial(string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator) : string
    {
        return $this->spaceFront.
            $this->renderSign($arithmeticSeparator).
            $this->renderNumber($this->number, $thousandsSeparator).
            $this->renderDecimals($decimalSeparator).
            $this->renderCurrency().
            $this->renderVAT().
            $this->spaceEnd;
    }

    private function renderCurrency() : string
    {
        return PriceFormatter::SPACE_PLACEHOLDER .$this->currency;
    }

    private function renderVAT() : string
    {
        if(!empty($this->vat))
        {
            return PriceFormatter::SPACE_PLACEHOLDER .$this->vat;
        }

        return '';
    }

    private function renderSign(string $arithmeticSeparator) : string
    {
        if(!empty($this->sign))
        {
            return $this->sign.$arithmeticSeparator;
        }

        return '';
    }

    private function renderDecimals(string $decimalSeparator) : string
    {
        if(!empty($this->decimals))
        {
            return $decimalSeparator.$this->decimals;
        }

        return '';
    }

    private function renderNumber(int $number, string $thousandsSeparator) : string
    {
        return number_format($number, 0, '.', $thousandsSeparator);
    }
}
