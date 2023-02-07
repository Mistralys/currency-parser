<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\GBP;

use Mistralys\CurrencyParser\BaseCurrencyFormat;
use Mistralys\CurrencyParser\Currencies\GBP;
use Mistralys\CurrencyParser\PriceFormatter;

/**
 * @property GBP $currency
 */
class GBP_GB extends BaseCurrencyFormat
{
    public function getCurrency(): GBP
    {
        return $this->currency;
    }

    public function getDecimalSeparator(): string
    {
        return '.';
    }

    public function getThousandsSeparator(): string
    {
        return ',';
    }

    public function getArithmeticSeparator(): string
    {
        return '';
    }

    public function getSymbolSeparator(): string
    {
        return '';
    }

    public function getSymbolPosition(): string
    {
        return PriceFormatter::SYMBOL_POSITION_FRONT;
    }

    public function getPreferredSymbolType(): string
    {
        return self::SYMBOL_TYPE_SYMBOL;
    }
}
