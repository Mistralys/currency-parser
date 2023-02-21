<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class PriceFormatter
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

    private string $nonBreakingSpace = '&#160;';
    private PriceMatch $workPrice;
    private string $decimalSeparator;
    private string $thousandsSeparator;
    private string $arithmeticSeparator = '';
    private array $symbolSpaceStyles = array(
        self::SYMBOL_POSITION_BEFORE_MINUS => null,
        self::SYMBOL_POSITION_AFTER_MINUS => null,
        self::SYMBOL_POSITION_END => null
    );
    private string $symbolPosition = self::SYMBOL_POSITION_AFTER_MINUS;
    private string $symbolMode = self::SYMBOL_MODE_PRESERVE;

    private function __construct(string $decimalSeparator, string $thousandsSeparator)
    {
        $this->setDecimalSeparator($decimalSeparator);
        $this->setThousandsSeparator($thousandsSeparator);
    }

    // region: A - Instance creation

    public static function createCustom(string $decimalSeparator, string $thousandsSeparator) : PriceFormatter
    {
        return new PriceFormatter($decimalSeparator, $thousandsSeparator);
    }

    /**
     * Creates a formatter for a specific currency locale:
     * It is automatically configured for that country's
     * typical price formatting.
     *
     * NOTE: It can be customised further, to use the locale's
     * formatting only as a template to start with.
     *
     * @param string|BaseCurrencyLocale $nameOrInstance Locale name as given to {@see Currencies::getLocaleByID()} or a locale instance.
     * @return PriceFormatter
     * @throws PriceFormatterException
     * @throws CurrencyParserException
     */
    public static function createForLocale($nameOrInstance) : PriceFormatter
    {
        $locale = Currencies::getInstance()->getLocale($nameOrInstance);

        return self::createCustom(
            $locale->getDecimalSeparator(),
            $locale->getThousandsSeparator()
        )
            ->setSymbolPosition($locale->getSymbolPosition())
            ->setSymbolSpaceStyles($locale->getSymbolSpaceStyles())
            ->setArithmeticSeparator($locale->getArithmeticSeparator());
    }

    // endregion

    // region: B - Utility methods

    public function setDecimalSeparator(string $separator) : self
    {
        $this->decimalSeparator = $separator;
        return $this;
    }

    public function setArithmeticSeparator(string $arithmeticSeparator): self
    {
        $this->arithmeticSeparator = $arithmeticSeparator;
        return $this;
    }

    public function setThousandsSeparator(string $thousandsSeparator): self
    {
        $this->thousandsSeparator = $thousandsSeparator;
        return $this;
    }

    public function setNonBreakingSpace(string $nonBreakingSpace): self
    {
        $this->nonBreakingSpace = $nonBreakingSpace;
        return $this;
    }

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

    /**
     * @param array<string,string> $styles Symbol position => space style pairs.
     * @return $this
     */
    public function setSymbolSpaceStyles(array $styles) : self
    {
        foreach($styles as $position => $style)
        {
            $this->setSymbolSpaceStyle($position, $style);
        }

        return $this;
    }

    /**
     * Sets the space style before and after the currency symbol,
     * for the target symbol position in the price.
     *
     * @param string $position The symbol position, e.g. {@see self::SYMBOL_POSITION_BEFORE_MINUS}.
     * @param string|NULL $style The space style to use, e.g. {@see self::SPACE_AFTER}, or NULL to use no spaces.
     * @return $this
     */
    public function setSymbolSpaceStyle(string $position, ?string $style) : self
    {
        $this->symbolSpaceStyles[$position] = $style;
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
        $style = $this->symbolSpaceStyles[$this->symbolPosition];
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
        if($this->symbolPosition === self::SYMBOL_POSITION_BEFORE_MINUS)
        {
            return $this->resolveSymbolWithSpacing();
        }

        return '';
    }

    private function renderSymbolAfterMinus() : string
    {
        if($this->symbolPosition === self::SYMBOL_POSITION_AFTER_MINUS)
        {
            return $this->resolveSymbolWithSpacing();
        }

        return '';
    }

    private function renderSymbolEnd() : string
    {
        if($this->symbolPosition === self::SYMBOL_POSITION_END)
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
            return '-'.$this->filterAddSpacePlaceholders($this->arithmeticSeparator);
        }

        return '';
    }

    private function renderDecimals() : string
    {
        if($this->workPrice->hasDecimals())
        {
            return $this->filterAddSpacePlaceholders($this->decimalSeparator).$this->workPrice->getDecimals();
        }

        return '';
    }

    private function renderNumber() : string
    {
        return number_format(
            $this->workPrice->getNumber(),
            0,
            '.',
            $this->filterAddSpacePlaceholders($this->thousandsSeparator)
        );
    }

    // endregion
}
