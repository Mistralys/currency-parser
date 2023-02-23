<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\CurrencyParser\Formatter\ReusableCustomFormatter;
use Mistralys\CurrencyParser\Formatter\ReusableLocaleFormatter;
use Mistralys\CurrencyParser\Interfaces\FormatterInterface;
use Mistralys\CurrencyParser\Interfaces\NonBreakingSpaceTrait;
use Mistralys\CurrencyParser\Interfaces\SymbolModesInterface;
use Mistralys\CurrencyParser\Interfaces\SymbolModesTrait;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

abstract class PriceFormatter
    implements
    FormatterInterface
{
    use SymbolModesTrait;
    use NonBreakingSpaceTrait;

    public const ERROR_INVALID_SYMBOL_POSITION = 129701;

    public const PLACEHOLDER_SPACE = '_space_';
    public const SYMBOL_POSITION_BEFORE_MINUS = 'before-minus';
    public const SYMBOL_POSITION_AFTER_MINUS = 'after-minus';
    public const SYMBOL_POSITION_END = 'end';
    public const SYMBOL_POSITIONS = array(
        self::SYMBOL_POSITION_END,
        self::SYMBOL_POSITION_AFTER_MINUS,
        self::SYMBOL_POSITION_BEFORE_MINUS
    );
    public const SPACE_BOTH = 'both';
    public const SPACE_AFTER = 'after';
    public const SPACE_BEFORE = 'before';

    protected PriceMatch $workPrice;

    // region: A - Instance creation

    public static function createCustom(?string $decimalSeparator=null, ?string $thousandsSeparator=null) : ReusableCustomFormatter
    {
        return new ReusableCustomFormatter($decimalSeparator, $thousandsSeparator);
    }

    /**
     * @param string|BaseCurrencyLocale $nameOrInstance
     * @return ReusableLocaleFormatter
     * @throws CurrencyParserException
     */
    public static function createLocale($nameOrInstance) : ReusableLocaleFormatter
    {
        return new ReusableLocaleFormatter(Currencies::getInstance()->getLocale($nameOrInstance));
    }

    // endregion

    // region: B - Utility methods

    abstract public function getDecimalSeparator(): string;

    abstract public function getArithmeticSeparator(): string;

    abstract public function getThousandsSeparator(): string;

    abstract public function getSymbolPosition(): string;

    /**
     * @return array<string,string|NULL>
     */
    abstract public function getSymbolSpaceStyles() : array;

    // endregion

    // region: C - Rendering

    protected function _format(PriceMatch $price) : string
    {
        $this->workPrice = $price;

        return str_replace(
            self::PLACEHOLDER_SPACE,
            $this->getNonBreakingSpace(),
            $this->render()
        );
    }

    protected function render() : string
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

    abstract public function getLocale() : ?BaseCurrencyLocale;

    private function resolveSymbol() : string
    {
        if($this->symbolMode === SymbolModesInterface::SYMBOL_MODE_NAME) {
            return $this->workPrice->getCurrencyName();
        }

        if($this->symbolMode === SymbolModesInterface::SYMBOL_MODE_SYMBOL) {
            return $this->workPrice->getCurrency()->getSymbol();
        }

        if($this->symbolMode === SymbolModesInterface::SYMBOL_MODE_PREFERRED) {
            $symbol = $this->resolvePreferredSymbol();
            if($symbol !== null) {
                return $symbol;
            }
        }

        return $this->workPrice->getMatchedCurrencySymbol();
    }

    private function resolvePreferredSymbol() : ?string
    {
        $locale = $this->getLocale();
        if($locale === null) {
            return null;
        }

        $type = $locale->getPreferredSymbolType();

        if($type === BaseCurrencyLocale::SYMBOL_TYPE_NAME) {
            return $this->workPrice->getCurrencyName();
        }

        return $this->workPrice->getCurrency()->getSymbol();
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
