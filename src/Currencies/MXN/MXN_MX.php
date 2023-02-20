<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\MXN;

use Mistralys\CurrencyParser\BaseCurrencyFormat;
use Mistralys\CurrencyParser\Currencies\MXN;
use Mistralys\CurrencyParser\PriceFormatter;

/**
 * @property MXN $currency
 */
class MXN_MX extends BaseCurrencyFormat
{
    public function getCurrency(): MXN
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
        return self::SYMBOL_TYPE_NAME;
    }
}
