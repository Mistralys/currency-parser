<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

use Mistralys\CurrencyParser\BaseCurrencyLocale;

interface FormatterInterface extends SymbolModesInterface, NonBreakingSpaceInterface
{
    public function getDecimalSeparator(): string;
    public function getArithmeticSeparator(): string;
    public function getThousandsSeparator(): string;
    public function getSymbolPosition(): string;

    /**
     * @return array<string,string|NULL>
     */
    public function getSymbolSpaceStyles() : array;
    public function getLocale() : ?BaseCurrencyLocale;
}
