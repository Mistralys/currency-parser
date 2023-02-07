<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies;

use Mistralys\CurrencyParser\BaseCurrency;

class MXN extends BaseCurrency
{
    public const CURRENCY_NAME = 'MXN';
    public const CURRENCY_SYMBOL = '$';
    public const CURRENCY_ENTITY_NUMBER = 36;

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
