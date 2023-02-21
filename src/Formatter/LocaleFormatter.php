<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Formatter;

use Mistralys\CurrencyParser\BaseCurrencyLocale;
use Mistralys\CurrencyParser\PriceFormatter;

class LocaleFormatter extends PriceFormatter
{
    private BaseCurrencyLocale $locale;

    public function __construct(BaseCurrencyLocale $locale)
    {
        $this->locale = $locale;
    }

    public function getDecimalSeparator(): string
    {
        return $this->locale->getDecimalSeparator();
    }

    public function getArithmeticSeparator(): string
    {
        return $this->locale->getArithmeticSeparator();
    }

    public function getThousandsSeparator(): string
    {
        return $this->locale->getThousandsSeparator();
    }

    public function getSymbolPosition(): string
    {
        return $this->locale->getSymbolPosition();
    }

    public function getSymbolSpaceStyles(): array
    {
        return $this->locale->getSymbolSpaceStyles();
    }

    public function getLocale(): BaseCurrencyLocale
    {
        return $this->locale;
    }
}