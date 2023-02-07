<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies;

use Mistralys\CurrencyParser\BaseCurrency;

class EUR extends BaseCurrency
{
    public const CURRENCY_NAME = 'EUR';
    public const CURRENCY_SYMBOL = '€';
    public const CURRENCY_ENTITY_NUMBER = 8211;

    public function getName(): string
    {
        return self::CURRENCY_NAME;
    }

    public function getSymbol(): string
    {
        return self::CURRENCY_SYMBOL;
    }

    public function getEntityNumber(): int
    {
        return self::CURRENCY_ENTITY_NUMBER;
    }
}
