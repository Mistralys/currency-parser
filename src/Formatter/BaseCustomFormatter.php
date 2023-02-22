<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Formatter;

use Mistralys\CurrencyParser\BaseCurrencyLocale;
use Mistralys\CurrencyParser\Currencies;
use Mistralys\CurrencyParser\PriceFormatter;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

abstract class BaseCustomFormatter extends PriceFormatter
{
    protected string $decimalSeparator;
    protected string $thousandsSeparator;
    protected string $arithmeticSeparator = '';
    protected string $symbolPosition = self::SYMBOL_POSITION_AFTER_MINUS;
    protected array $symbolSpaceStyles = array(
        self::SYMBOL_POSITION_BEFORE_MINUS => null,
        self::SYMBOL_POSITION_AFTER_MINUS => null,
        self::SYMBOL_POSITION_END => null
    );

    public function __construct(?string $decimalSeparator=null, ?string $thousandsSeparator=null)
    {
        if($decimalSeparator !== null) { $this->setDecimalSeparator($decimalSeparator); }
        if($thousandsSeparator !== null) { $this->setThousandsSeparator($thousandsSeparator); }
    }

    public function getLocale(): ?BaseCurrencyLocale
    {
        return null;
    }

    /**
     * Configures the formatter for a specific currency locale.
     * This allows using the locale's settings as a template
     * to customise further.
     *
     * @param string|BaseCurrencyLocale $nameOrInstance Locale name as given to {@see Currencies::getLocaleByID()} or a locale instance.
     * @return $this
     * @throws PriceFormatterException
     * @throws CurrencyParserException
     */
    public function configureWithLocale($nameOrInstance) : self
    {
        $locale = Currencies::getInstance()->getLocale($nameOrInstance);

        return $this
            ->setDecimalSeparator($locale->getDecimalSeparator())
            ->setThousandsSeparator($locale->getThousandsSeparator())
            ->setSymbolPosition($locale->getSymbolPosition())
            ->setSymbolSpaceStyles($locale->getSymbolSpaceStyles())
            ->setArithmeticSeparator($locale->getArithmeticSeparator());
    }

    public function getDecimalSeparator(): string
    {
        return $this->decimalSeparator;
    }

    public function getArithmeticSeparator(): string
    {
        return $this->arithmeticSeparator;
    }

    public function getThousandsSeparator(): string
    {
        return $this->thousandsSeparator;
    }

    public function getSymbolPosition(): string
    {
        return $this->symbolPosition;
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

    public function getSymbolSpaceStyles(): array
    {
        return $this->symbolSpaceStyles;
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

    public function setSymbolSpaceBeforeMinus(string $style) : self
    {
        return $this->setSymbolSpaceStyle(self::SYMBOL_POSITION_BEFORE_MINUS, $style);
    }

    public function setSymbolSpaceAfterMinus(string $style) : self
    {
        return $this->setSymbolSpaceStyle(self::SYMBOL_POSITION_AFTER_MINUS, $style);
    }

    public function setSymbolSpaceAtTheEnd(string $style) : self
    {
        return $this->setSymbolSpaceStyle(self::SYMBOL_POSITION_END, $style);
    }

    public function setDecimalSeparator(string $separator) : self
    {
        $this->decimalSeparator = $separator;
        return $this;
    }
}
