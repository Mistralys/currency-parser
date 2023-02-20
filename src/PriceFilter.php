<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\FileHelper\FileInfo;

class PriceFilter
{
    private PriceFormatter $formatter;
    private PriceParser $parser;

    private function __construct(PriceFormatter $formatter, PriceParser $parser)
    {
        $this->formatter = $formatter;
        $this->parser = $parser;
    }

    public static function create(PriceFormatter $formatter, PriceParser $parser) : PriceFilter
    {
        return new PriceFilter($formatter, $parser);
    }

    /**
     * @param string|BaseCurrency $currency
     * @param PriceFormatter $formatter
     * @return PriceFilter
     */
    public static function createForCurrency($currency, PriceFormatter $formatter) : PriceFilter
    {
        return self::create(
            $formatter,
            PriceParser::create()->expectCurrency($currency)
        );
    }

    public function filterString(string $subject) : string
    {
        $prices = $this->parser->findPrices($subject);
        $replaces = array();

        foreach($prices as $price)
        {
            $replaces[$price->getMatchedString()] = $this->formatter->formatPrice($price);
        }

        return str_replace(
            array_keys($replaces),
            array_values($replaces),
            $subject
        );
    }

    public function filterFile(FileInfo $file) : string
    {
        return $this->filterString($file->getContents());
    }
}
