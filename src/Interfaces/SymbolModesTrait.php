<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

use Mistralys\CurrencyParser\Formatter\PriceFormatterException;

trait SymbolModesTrait
{
    protected ?string $symbolMode = null;

    public function getSymbolMode() : string
    {
        return $this->symbolMode ?? SymbolModesInterface::SYMBOL_MODE_PRESERVE;
    }

    /**
     * @param string $mode
     * @return $this
     * @throws PriceFormatterException {@see self::ERROR_INVALID_SYMBOL_MODE}
     */
    public function setSymbolMode(string $mode) : self
    {
        $this->requireSymbolModeValid($mode);

        $this->symbolMode = $mode;

        return $this;
    }

    /**
     * The currency symbol style will be preserved for all prices,
     * so that they can use USD or $ interchangeably for example.
     * In effect, this will leave all symbols as-is.
     *
     * @return $this
     * @throws PriceFormatterException
     */
    public function setSymbolModePreserve() : self
    {
        return $this->setSymbolMode(SymbolModesInterface::SYMBOL_MODE_PRESERVE);
    }

    /**
     * All currency symbols will be replaced by the actual currency
     * symbol, even if they used the named variant in the source
     * string.
     *
     * @return $this
     * @throws PriceFormatterException
     */
    public function setSymbolModeSymbol() : self
    {
        return $this->setSymbolMode(SymbolModesInterface::SYMBOL_MODE_SYMBOL);
    }

    /**
     * All currency symbols will be replaced by the currency name,
     * even if they used the symbol in the source string.
     *
     * @return $this
     * @throws PriceFormatterException
     */
    public function setSymbolModeName() : self
    {
        return $this->setSymbolMode(SymbolModesInterface::SYMBOL_MODE_NAME);
    }

    public function setSymbolModePreferred() : self
    {
        return $this->setSymbolMode(SymbolModesInterface::SYMBOL_MODE_PREFERRED);
    }

    /**
     * @param string $mode
     * @return void
     * @throws PriceFormatterException {@see SymbolModesInterface::ERROR_INVALID_SYMBOL_MODE}
     */
    public function requireSymbolModeValid(string $mode) : void
    {
        if(in_array($mode, SymbolModesInterface::SYMBOL_MODES)) {
            return;
        }

        throw new PriceFormatterException(
            'Invalid price formatter symbol mode.',
            sprintf(
                'The mode [%s] is unknown. Valid modes are: [%s].',
                $mode,
                implode(', ', SymbolModesInterface::SYMBOL_MODES)
            ),
            SymbolModesInterface::ERROR_INVALID_SYMBOL_MODE
        );
    }
}
