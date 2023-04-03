<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppLocalize\Localization;
use AppLocalize\Localization_Country;
use AppLocalize\Localization_Exception;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper_Exception;
use Mistralys\CurrencyParser\Formatter\PriceFormatterException;
use Mistralys\CurrencyParser\Interfaces\NonBreakingSpaceInterface;
use Mistralys\CurrencyParser\Interfaces\NonBreakingSpaceTrait;
use Mistralys\CurrencyParser\Interfaces\ReusableFormatterInterface;
use Mistralys\CurrencyParser\Interfaces\SymbolModesInterface;
use Mistralys\CurrencyParser\Interfaces\SymbolModesTrait;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class PriceFilter
    implements
    SymbolModesInterface,
    NonBreakingSpaceInterface
{
    use SymbolModesTrait;
    use NonBreakingSpaceTrait;

    /**
     * @var array<string,ReusableFormatterInterface>
     */
    private array $formatters = array();

    private PriceParser $parser;

    private function __construct(?PriceParser $parser=null)
    {
        $this->parser = $parser ?? PriceParser::create();
    }

    // region: A - Creating instances

    /**
     * Creates a filter for an existing parser instance: will format
     * all currencies that the parser is configured for.
     *
     * @param PriceParser|NULL $parser Optional parser instance to use.
     * @return PriceFilter
     */
    public static function create(?PriceParser $parser=null) : PriceFilter
    {
        return new PriceFilter($parser);
    }

    /**
     * Creates a filter for the specified currency locales. The parser
     * instance is created automatically for the related currencies,
     * and formatters set for each.
     *
     * @param string|BaseCurrencyLocale ...$locales Locale names (e.g. "EUR_FR", "USD") or locale instances.
     * @return PriceFilter
     * @throws CurrencyParserException
     */
    public static function createForLocales(...$locales) : PriceFilter
    {
        $collection = Currencies::getInstance();
        $filter = self::create();

        foreach($locales as $nameOrInstance)
        {
            $filter->setFormatterByLocale($collection->getLocale($nameOrInstance));
        }

        return $filter;
    }

    /**
     * Creates a filter and configures currency locales for the
     * specified countries.
     *
     * @param string|Localization_Country ...$countries
     * @return PriceFilter
     * @throws CurrencyParserException
     * @throws PriceFormatterException
     * @throws Localization_Exception
     */
    public static function createForCountries(...$countries) : PriceFilter
    {
        $filter = self::create();

        foreach($countries as $isoOrInstance)
        {
            if($isoOrInstance instanceof Localization_Country) {
                $country = $isoOrInstance;
            } else {
                $country = Localization::createCountry($isoOrInstance);
            }

            $filter->setFormatterByCountry($country);
        }

        return $filter;
    }

    // endregion

    // region: C - Handling formatters

    /**
     * @param string|BaseCurrencyLocale $localeNameOrInstance
     * @return $this
     * @throws CurrencyParserException
     * @throws PriceFormatterException
     */
    public function setFormatterByLocale($localeNameOrInstance) : self
    {
        $locale = Currencies::getInstance()->getLocale($localeNameOrInstance);

        return $this->setFormatter(
            $locale,
            PriceFormatter::createLocale($locale)
        );
    }

    /**
     * Sets a currency locale formatter using the specified
     * country instance.
     *
     * @param Localization_Country $country
     * @return $this
     * @throws CurrencyParserException
     * @throws PriceFormatterException
     */
    public function setFormatterByCountry(Localization_Country $country) : self
    {
        return $this->setFormatterByLocale(Currencies::getInstance()->getLocaleByCountry($country));
    }

    /**
     * Sets a specific formatter to use for the target currency.
     *
     * NOTE: This is currency-locale-agnostic on purpose, so
     * custom formatters may be used for a currency.
     *
     * @param string|BaseCurrencyLocale $localeNameOrInstance
     * @param ReusableFormatterInterface $formatter
     * @return $this
     * @throws CurrencyParserException
     */
    public function setFormatter($localeNameOrInstance, ReusableFormatterInterface $formatter) : self
    {
        $locale = Currencies::getInstance()->getLocale($localeNameOrInstance);

        $this->parser->expectCurrency($locale);
        $this->formatters[$locale->getCurrency()->getName()] = $formatter;
        return $this;
    }

    /**
     * @param string|BaseCurrencyLocale $localeNameOrInstance
     * @return ReusableFormatterInterface|null
     * @throws CurrencyParserException
     */
    public function getFormatter($localeNameOrInstance) : ?ReusableFormatterInterface
    {
        $locale = Currencies::getInstance()->getLocale($localeNameOrInstance);

        return $this->formatters[$locale->getCurrencyName()] ?? null;
    }

    /**
     * Checks whether a formatter has been set for the specified currency.
     *
     * @param string|BaseCurrencyLocale $localeNameOrInstance
     * @return bool
     * @throws CurrencyParserException
     */
    public function hasFormatter($localeNameOrInstance) : bool
    {
        return $this->getFormatter($localeNameOrInstance) !== null;
    }

    // endregion


    // region: Utility methods

    // endregion

    // region: B - Filtering methods

    /**
     * @param FileInfo $file
     * @return string
     * @throws CurrencyParserException
     * @throws PriceFilterException
     * @throws PriceFormatterException
     * @throws FileHelper_Exception
     */
    public function filterFile(FileInfo $file) : string
    {
        return $this->filterString($file->getContents());
    }

    /**
     * @param string $subject
     * @return string
     * @throws PriceFilterException
     * @throws PriceFormatterException
     * @throws CurrencyParserException
     */
    public function filterString(string $subject) : string
    {
        $prices = $this->parser->findPrices($subject);
        $replaces = array();

        foreach($prices as $price)
        {
            $replaces[$price->getMatchedString()] = $this->resolveFormatter($price)->format($price);
        }

        return str_replace(
            array_keys($replaces),
            array_values($replaces),
            $subject
        );
    }

    // endregion

    /**
     * @param PriceMatch $price
     * @return ReusableFormatterInterface
     * @throws CurrencyParserException
     * @throws PriceFormatterException
     */
    private function resolveFormatter(PriceMatch $price) : ReusableFormatterInterface
    {
        $locale = $price->getLocale();
        $formatter = $this->getFormatter($locale);

        if($formatter === null) {
            $formatter = $locale->createFormatter();
            $this->setFormatter($locale, $formatter);
        }

        // Pass on the settings that have been set for the filter

        if(isset($this->symbolMode)) {
            $formatter->setSymbolMode($this->getSymbolMode());
        }

        if(isset($this->nonBreakingSpace)) {
            $formatter->setNonBreakingSpace($this->getNonBreakingSpace());
        }

        return $formatter;
    }

    public function setDebugEnabled(bool $enabled=true) : self
    {
        $this->parser->setDebugEnabled($enabled);
        return $this;
    }
}
