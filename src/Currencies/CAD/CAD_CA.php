<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\CAD;

use Mistralys\CurrencyParser\BaseCurrencyLocale;
use Mistralys\CurrencyParser\Currencies\CAD;
use Mistralys\CurrencyParser\PriceFormatter;

/**
 * @property CAD $currency
 */
class CAD_CA extends BaseCurrencyLocale
{
    public function getCurrency(): CAD
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
        return PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS; // $ -50
    }

    public function getPreferredSymbolType(): string
    {
        return self::SYMBOL_TYPE_SYMBOL;
    }

    public function getSymbolSpaceStyles(): array
    {
        return array(
            PriceFormatter::SYMBOL_POSITION_END => PriceFormatter::SPACE_BEFORE, // 50 $
            PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS => PriceFormatter::SPACE_AFTER, // $ -50
            PriceFormatter::SYMBOL_POSITION_AFTER_MINUS => PriceFormatter::SPACE_AFTER // -$ 50
        );
    }
}
