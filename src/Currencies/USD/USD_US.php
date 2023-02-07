<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\USD;

use Mistralys\CurrencyParser\BaseCurrencyFormat;
use Mistralys\CurrencyParser\Currencies\USD;
use Mistralys\CurrencyParser\PriceFormatter;

/**
 * @property USD $currency
 */
class USD_US extends BaseCurrencyFormat
{
    public function getCurrency(): USD
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
