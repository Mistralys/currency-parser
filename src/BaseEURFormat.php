<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\CurrencyParser\Currencies\EUR;

/**
 * @property EUR $currency
 */
abstract class BaseEURFormat extends BaseCurrencyFormat
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
}
