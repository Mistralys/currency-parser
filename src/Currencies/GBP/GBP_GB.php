<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\GBP;

use Mistralys\CurrencyParser\BaseCurrencyLocale;
use Mistralys\CurrencyParser\Currencies\GBP;
use Mistralys\CurrencyParser\PriceFormatter;

/**
 * @property GBP $currency
 */
class GBP_GB extends BaseCurrencyLocale
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
        return PriceFormatter::SYMBOL_POSITION_AFTER_MINUS; // -£50
    }

    public function getPreferredSymbolType(): string
    {
        return self::SYMBOL_TYPE_SYMBOL;
    }

    public function getSymbolSpaceStyles(): array
    {
        return array(
            PriceFormatter::SYMBOL_POSITION_END => null, // 50£
            PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS => null, // £-50
            PriceFormatter::SYMBOL_POSITION_AFTER_MINUS => null // -£50
        );
    }
}
