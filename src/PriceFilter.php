<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\FileHelper\FileInfo;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;
use function AppUtils\parseVariable;

class PriceFilter
{
    public const ERROR_INVALID_CURRENCY = 129801;

    /**
     * @var PriceFormatter[]
     */
    private array $formatters = array();

    private PriceParser $parser;

    private function __construct(PriceParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Creates a filter for an existing parser instance: will format
     * all currencies that the parser is configured for.
     *
     * @param PriceParser $parser
     * @return PriceFilter
     */
    public static function create(PriceParser $parser) : PriceFilter
    {
        return new PriceFilter($parser);
    }

    /**
     * Creates a filter for the specified currencies. The parser
     * instance is created automatically for the currencies.
     *
     * @param string|BaseCurrency ...$currencies Currency names (e.g. "USD") or currency instances.
     * @return PriceFilter
     * @throws CurrencyParserException
     */
    public static function createForCurrencies(...$currencies) : PriceFilter
    {
        return self::create(
            PriceParser::create()
                ->expectCurrencies(...$currencies)
        );
    }

    /**
     * Sets a specific formatter to use for the target currency.
     *
     * @param string|BaseCurrency $currencyNameOrInstance
     * @param PriceFormatter $formatter
     * @return $this
     * @throws PriceFilterException
     */
    public function setFormatter($currencyNameOrInstance, PriceFormatter $formatter) : self
    {
        $currency = Currencies::getInstance()->resolveCurrency($currencyNameOrInstance);

        if($currency !== null) {
            $this->formatters[$currency->getName()] = $formatter;
            return $this;
        }

        throw new PriceFilterException(
            'Invalid currency specified.',
            sprintf(
                'Could not determine the currency to use. Given: [%s].',
                parseVariable($currencyNameOrInstance)->enableType()->toString()
            ),
            self::ERROR_INVALID_CURRENCY
        );
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
            $replaces[$price->getMatchedString()] = $this->resolveFormatter($price)->formatPrice($price);
        }

        return str_replace(
            array_keys($replaces),
            array_values($replaces),
            $subject
        );
    }

    public function getFormatter(BaseCurrency $currency) : ?PriceFormatter
    {
        return $this->formatters[$currency->getName()] ?? null;
    }

    /**
     * @param PriceMatch $price
     * @return PriceFormatter
     * @throws PriceFilterException
     * @throws PriceFormatterException
     */
    private function resolveFormatter(PriceMatch $price) : PriceFormatter
    {
        $currency = $price->getCurrency();
        $formatter = $this->getFormatter($currency);

        if($formatter !== null) {
            return $formatter;
        }

        $formatter = PriceFormatter::createForLocale($currency->getDefaultLocale());
        $this->setFormatter($currency, $formatter);

        return $formatter;
    }

    public function filterFile(FileInfo $file) : string
    {
        return $this->filterString($file->getContents());
    }
}
