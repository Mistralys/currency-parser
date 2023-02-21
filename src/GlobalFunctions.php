<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use Mistralys\CurrencyParser\Formatter\PriceFormatterException;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

/**
 * @param string|BaseCurrencyLocale $nameOrInstance
 * @return BaseCurrencyLocale
 * @throws CurrencyParserException
 */
function currencyLocale($nameOrInstance) : BaseCurrencyLocale
{
    return Currencies::getInstance()->getLocale($nameOrInstance);
}

/**
 * Finds all written prices in the subject text or markup.
 *
 * @param string $subject
 * @param string|BaseCurrency ...$currencies
 * @return PriceMatches
 * @throws CurrencyParserException
 */
function findPrices(string $subject, ...$currencies) : PriceMatches
{
    $parser = PriceParser::create();

    if(empty($currencies)) {
        $parser->expectAnyCurrency();
    } else {
         $parser->expectCurrencies(...$currencies);
    }

    return $parser->findPrices($subject);
}

/**
 * @param string $subject
 * @param string|BaseCurrencyLocale ...$locales
 * @return string
 * @throws CurrencyParserException
 * @throws PriceFormatterException
 * @throws PriceFilterException
 */
function filterString(string $subject, ...$locales) : string
{
    return PriceFilter::createForLocales(...$locales)->filterString($subject);
}

/**
 * @param string $price
 * @param $currency
 * @return PriceMatch|null
 * @throws CurrencyParserException
 */
function tryParsePrice(string $price, $currency=null) : ?PriceMatch
{
    return PriceParser::tryParsePrice($price, $currency);
}

/**
 * @param string $price
 * @param $currency
 * @return PriceMatch
 * @throws CurrencyParserException
 */
function parsePrice(string $price, $currency=null) : PriceMatch
{
    return PriceParser::parsePrice($price, $currency);
}
