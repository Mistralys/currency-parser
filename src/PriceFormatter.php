<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

class PriceFormatter
{
    public const ERROR_INVALID_SYMBOL_MODE = 129701;
    public const ERROR_INVALID_SYMBOL_POSITION = 129702;

    public const SPACE_PLACEHOLDER = '_space_';
    public const SYMBOL_POSITION_BEFORE_MINUS = 'before-minus';
    public const SYMBOL_POSITION_AFTER_MINUS = 'after-minus';
    public const SYMBOL_POSITION_END = 'end';
    public const SYMBOL_MODE_PRESERVE = 'preserve';
    public const SYMBOL_MODE_NAME = 'name';
    public const SYMBOL_MODE_SYMBOL = 'symbol';
    public const SYMBOL_POSITIONS = array(
        self::SYMBOL_POSITION_END,
        self::SYMBOL_POSITION_AFTER_MINUS,
        self::SYMBOL_POSITION_BEFORE_MINUS
    );
    public const SYMBOL_MODES = array(
        self::SYMBOL_MODE_NAME,
        self::SYMBOL_MODE_PRESERVE,
        self::SYMBOL_MODE_SYMBOL
    );

    private string $nonBreakingSpace = '&#160;';
    private PriceMatch $workPrice;
    private string $decimalSeparator;
    private string $thousandsSeparator;
    private string $arithmeticSeparator;
    private bool $symbolSpaceEnabled = false;
    private string $symbolPosition = self::SYMBOL_POSITION_AFTER_MINUS;
    private string $symbolMode = self::SYMBOL_MODE_PRESERVE;

    private function __construct(string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator)
    {
        $this->decimalSeparator = $decimalSeparator;
        $this->thousandsSeparator = $thousandsSeparator;
        $this->arithmeticSeparator = $arithmeticSeparator;
    }

    public static function create(string $decimalSeparator, string $thousandsSeparator, string $arithmeticSeparator='') : PriceFormatter
    {
        return new PriceFormatter($decimalSeparator, $thousandsSeparator, $arithmeticSeparator);
    }

    // region: A - Utility methods

    /**
     * @param string $position
     * @return $this
     * @throws PriceFormatterException {@see self::ERROR_INVALID_SYMBOL_POSITION}
     */
    public function setSymbolPosition(string $position) : self
    {
        if(in_array($position, self::SYMBOL_POSITIONS)) {
            $this->symbolPosition = $position;
            return $this;
        }

        throw new PriceFormatterException(
            'Invalid price formatter symbol position.',
            sprintf(
                'The position [%s] is unknown. Valid positions are: [%s].',
                $position,
                implode(', ', self::SYMBOL_POSITIONS)
            ),
            self::ERROR_INVALID_SYMBOL_POSITION
        );
    }

    public function setSymbolSpaceEnabled(bool $enabled=true) : self
    {
        $this->symbolSpaceEnabled = $enabled;
        return $this;
    }

    /**
     * @param string $mode
     * @return $this
     * @throws PriceFormatterException {@see self::ERROR_INVALID_SYMBOL_MODE}
     */
    public function setSymbolMode(string $mode) : self
    {
        if(in_array($mode, self::SYMBOL_MODES)) {
            $this->symbolMode = $mode;
            return $this;
        }

        throw new PriceFormatterException(
            'Invalid price formatter symbol mode.',
            sprintf(
                'The mode [%s] is unknown. Valid modes are: [%s].',
                $mode,
                implode(', ', self::SYMBOL_MODES)
            ),
            self::ERROR_INVALID_SYMBOL_MODE
        );
    }

    public function formatPrice(PriceMatch $price) : string
    {
        $this->workPrice = $price;

        $result = str_replace(
            self::SPACE_PLACEHOLDER,
            $this->nonBreakingSpace,
            $this->render()
        );

        unset($this->workPrice);

        return $result;
    }

    // endregion

    // region: Rendering

    private function render() : string
    {
        return
            $this->workPrice->getSpaceFront().
            $this->renderSymbolBeforeMinus().
            $this->renderSign().
            $this->renderSymbolAfterMinus().
            $this->renderNumber().
            $this->renderDecimals().
            $this->renderSymbolEnd().
            $this->renderVAT().
            $this->workPrice->getSpaceEnd();
    }

    private function resolveSymbol() : string
    {
        if($this->symbolMode === self::SYMBOL_MODE_NAME) {
            return $this->workPrice->getCurrencyName();
        }

        if($this->symbolMode === self::SYMBOL_MODE_SYMBOL) {
            return $this->workPrice->getCurrency()->getSymbol();
        }

        return $this->workPrice->getMatchedCurrencySymbol();
    }

    private function resolveSymbolSpace() : string
    {
        if($this->symbolSpaceEnabled) {
            return self::SPACE_PLACEHOLDER;
        }

        return '';
    }

    private function renderSymbolBeforeMinus() : string
    {
        if($this->symbolPosition === self::SYMBOL_POSITION_BEFORE_MINUS)
        {
            return $this->resolveSymbol() . $this->resolveSymbolSpace();
        }

        return '';
    }

    private function renderSymbolAfterMinus() : string
    {
        if($this->symbolPosition === self::SYMBOL_POSITION_AFTER_MINUS)
        {
            return $this->resolveSymbolSpace() . $this->resolveSymbol();
        }

        return '';
    }

    private function renderSymbolEnd() : string
    {
        if($this->symbolPosition === self::SYMBOL_POSITION_END)
        {
            return $this->resolveSymbolSpace() . $this->resolveSymbol();
        }

        return '';
    }

    private function renderVAT() : string
    {
        if($this->workPrice->hasVAT())
        {
            return self::SPACE_PLACEHOLDER .$this->workPrice->getVAT();
        }

        return '';
    }

    private function renderSign() : string
    {
        if($this->workPrice->isNegative())
        {
            return '-'.$this->arithmeticSeparator;
        }

        return '';
    }

    private function renderDecimals() : string
    {
        if($this->workPrice->hasDecimals())
        {
            return $this->decimalSeparator.$this->workPrice->getDecimals();
        }

        return '';
    }

    private function renderNumber() : string
    {
        return number_format(
            $this->workPrice->getNumber(),
            0,
            '.',
            $this->thousandsSeparator
        );
    }

    // endregion
}
