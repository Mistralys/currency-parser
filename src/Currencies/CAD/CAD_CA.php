<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\CAD;

use Mistralys\CurrencyParser\BaseCurrencyLocale;
use Mistralys\CurrencyParser\Currencies\USD;
use Mistralys\CurrencyParser\PriceFormatter;

/**
 * @property USD $currency
 */
class CAD_CA extends BaseCurrencyLocale
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
        return ' ';
    }

    public function getSymbolPosition(): string
    {
        return PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS;
    }

    public function getPreferredSymbolType(): string
    {
        return self::SYMBOL_TYPE_SYMBOL;
    }
}
