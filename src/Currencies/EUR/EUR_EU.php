<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\CurrencyParser\Currencies\EUR;

/**
 * @property EUR $currency
 */
class EUR_EU extends BaseCurrencyLocale
{
    public function __construct(EUR $currency)
    {
        parent::__construct($currency);
    }

    public function getCurrency(): EUR
    {
        return $this->currency;
    }

    public function getDecimalSeparator(): string
    {
        return ',';
    }

    public function getThousandsSeparator(): string
    {
        return '.';
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
        return PriceFormatter::SYMBOL_POSITION_END;
    }

    public function getPreferredSymbolType(): string
    {
        return self::SYMBOL_TYPE_SYMBOL;
    }

    public function getSymbolSpaceStyles(): array
    {
        return array(
            PriceFormatter::SYMBOL_POSITION_END => PriceFormatter::SPACE_BEFORE,
            PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS => null,
            PriceFormatter::SYMBOL_POSITION_AFTER_MINUS => PriceFormatter::SPACE_AFTER
        );
    }
}
