<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

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
 * @param string|BaseCurrencyLocale ...$locales
 * @return PriceMatches
 * @throws CurrencyParserException
 */
function findPrices(string $subject, ...$locales) : PriceMatches
{
    $parser = PriceParser::create();

    if(empty($locales)) {
        $parser->expectAnyCurrency();
    } else {
         $parser->expectCurrencies(...$locales);
    }

    return $parser->findPrices($subject);
}

/**
 * @param string $price
 * @param string|BaseCurrencyLocale|NULL $localeNameOrInstance
 * @return PriceMatch|null
 * @throws CurrencyParserException
 */
function tryParsePrice(string $price, $localeNameOrInstance=null) : ?PriceMatch
{
    return PriceParser::tryParsePrice($price, $localeNameOrInstance);
}

/**
 * Parses and returns a single price string.
 *
 * NOTE: Throws an exception if no price can be recognized.
 * See {@see tryParsePrice()} as an alternative if unsure
 * whether the string contains a valid price.
 *
 * @param string $price
 * @param string|BaseCurrencyLocale $localeNameOrInstance
 * @return PriceMatch
 * @throws CurrencyParserException
 */
function parsePrice(string $price, $localeNameOrInstance=null) : PriceMatch
{
    return PriceParser::parsePrice($price, $localeNameOrInstance);
}
