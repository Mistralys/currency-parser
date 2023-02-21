<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

interface SymbolModesInterface
{
    public const ERROR_INVALID_SYMBOL_MODE = 130101;

    public const SYMBOL_MODE_PRESERVE = 'preserve';
    public const SYMBOL_MODE_SYMBOL = 'symbol';
    public const SYMBOL_MODE_NAME = 'name';
    public const SYMBOL_MODE_PREFERRED = 'preferred';

    public const SYMBOL_MODES = array(
        self::SYMBOL_MODE_NAME,
        self::SYMBOL_MODE_PRESERVE,
        self::SYMBOL_MODE_SYMBOL,
        self::SYMBOL_MODE_PREFERRED
    );

    public function getSymbolMode() : string;
    public function setSymbolMode(string $mode) : self;
    public function setSymbolModePreserve() : self;
    public function setSymbolModeSymbol() : self;
    public function setSymbolModeName() : self;
    public function setSymbolModePreferred() : self;
}
