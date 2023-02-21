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
