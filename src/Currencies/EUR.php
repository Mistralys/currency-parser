<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies;

use Mistralys\CurrencyParser\BaseCurrency;

class EUR extends BaseCurrency
{
    public const CURRENCY_NAME = 'EUR';
    public const CURRENCY_SYMBOL = '€';
    public const CURRENCY_ENTITY_NUMBER = 8364;
    public const CURRENCY_DEFAULT_ISO = 'EU';

    public function getName(): string
    {
        return self::CURRENCY_NAME;
    }

    public function getDefaultLocaleISO(): string
    {
        return self::CURRENCY_DEFAULT_ISO;
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
