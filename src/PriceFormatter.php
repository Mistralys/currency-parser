<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\CurrencyParser\Formatter\CustomFormatter;
use Mistralys\CurrencyParser\Formatter\LocaleFormatter;
use Mistralys\CurrencyParser\Formatter\PriceFormatterException;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

abstract class PriceFormatter
{
    public const ERROR_INVALID_SYMBOL_MODE = 129701;
    public const ERROR_INVALID_SYMBOL_POSITION = 129702;

    public const PLACEHOLDER_SPACE = '_space_';
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
    public const SPACE_BOTH = 'both';
    public const SPACE_AFTER = 'after';
    public const SPACE_BEFORE = 'before';

    protected string $nonBreakingSpace = '&#160;';
    private PriceMatch $workPrice;
    protected string $symbolMode = self::SYMBOL_MODE_PRESERVE;

    // region: A - Instance creation

    public static function createCustom(?string $decimalSeparator=null, ?string $thousandsSeparator=null) : CustomFormatter
    {
        return new CustomFormatter($decimalSeparator, $thousandsSeparator);
    }

    /**
     * @param string|BaseCurrencyLocale $nameOrInstance
     * @return LocaleFormatter
     * @throws CurrencyParserException
     */
    public static function createLocale($nameOrInstance) : LocaleFormatter
    {
        return new LocaleFormatter(Currencies::getInstance()->getLocale($nameOrInstance));
    }

    // endregion

    // region: B - Utility methods

    abstract public function getDecimalSeparator(): string;

    abstract public function getArithmeticSeparator(): string;

    abstract public function getThousandsSeparator(): string;

    abstract public function getSymbolPosition(): string;

    /**
     * @return array<string,string>
     */
    abstract public function getSymbolSpaceStyles() : array;

    public function getNonBreakingSpace(): string
    {
        return $this->nonBreakingSpace;
    }

    public function setNonBreakingSpace(string $nonBreakingSpace): self
    {
        $this->nonBreakingSpace = $nonBreakingSpace;
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
        return $this->setSymbolMode(self::SYMBOL_MODE_PRESERVE);
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
        return $this->setSymbolMode(self::SYMBOL_MODE_SYMBOL);
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
        return $this->setSymbolMode(self::SYMBOL_MODE_NAME);
    }

    public function getSymbolMode() : string
    {
        return $this->symbolMode;
    }

    public function formatPrice(PriceMatch $price) : string
    {
        $this->workPrice = $price;

        $result = str_replace(
            self::PLACEHOLDER_SPACE,
            $this->nonBreakingSpace,
            $this->render()
        );

        unset($this->workPrice);

        return $result;
    }

    // endregion

    // region: C - Rendering

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

    private function filterAddSpacePlaceholders(string $subject) : string
    {
        return str_replace(' ', self::PLACEHOLDER_SPACE, $subject);
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

    private function resolveSymbolWithSpacing() : string
    {
        $styles = $this->getSymbolSpaceStyles();
        $style = $styles[$this->getSymbolPosition()];
        $symbol = $this->resolveSymbol();

        if($style === self::SPACE_AFTER) {
            return $symbol.self::PLACEHOLDER_SPACE;
        }

        if($style === self::SPACE_BEFORE) {
            return self::PLACEHOLDER_SPACE.$symbol;
        }

        if($style === self::SPACE_BOTH) {
            return self::PLACEHOLDER_SPACE.$symbol.self::PLACEHOLDER_SPACE;
        }

        return $symbol;
    }

    private function renderSymbolBeforeMinus() : string
    {
        if($this->getSymbolPosition() === self::SYMBOL_POSITION_BEFORE_MINUS)
        {
            return $this->resolveSymbolWithSpacing();
        }

        return '';
    }

    private function renderSymbolAfterMinus() : string
    {
        if($this->getSymbolPosition() === self::SYMBOL_POSITION_AFTER_MINUS)
        {
            return $this->resolveSymbolWithSpacing();
        }

        return '';
    }

    private function renderSymbolEnd() : string
    {
        if($this->getSymbolPosition() === self::SYMBOL_POSITION_END)
        {
            return $this->resolveSymbolWithSpacing();
        }

        return '';
    }

    private function renderVAT() : string
    {
        if($this->workPrice->hasVAT())
        {
            return self::PLACEHOLDER_SPACE.$this->workPrice->getVAT();
        }

        return '';
    }

    private function renderSign() : string
    {
        if($this->workPrice->isNegative())
        {
            return '-'.$this->filterAddSpacePlaceholders($this->getArithmeticSeparator());
        }

        return '';
    }

    private function renderDecimals() : string
    {
        if($this->workPrice->hasDecimals())
        {
            return $this->filterAddSpacePlaceholders($this->getDecimalSeparator()).$this->workPrice->getDecimals();
        }

        return '';
    }

    private function renderNumber() : string
    {
        return number_format(
            $this->workPrice->getNumber(),
            0,
            '.',
            $this->filterAddSpacePlaceholders($this->getThousandsSeparator())
        );
    }

    // endregion
}
