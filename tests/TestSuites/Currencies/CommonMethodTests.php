<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Currencies;

use Mistralys\CurrencyParser\Currencies;
use Mistralys\CurrencyParser\Currencies\EUR;
use Mistralys\CurrencyParser\Currencies\EUR\EUR_EU;
use Mistralys\CurrencyParser\Currencies\EUR\EUR_FR;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class CommonMethodTests extends CurrencyParserTestCase
{
    public function test_getByName() : void
    {
        $this->assertInstanceOf(EUR::class, Currencies::getInstance()->getByName('EUR'));
        $this->assertInstanceOf(EUR::class, Currencies::getInstance()->getByName('eur'));
    }

    public function test_getLocaleByID() : void
    {
        $this->assertInstanceOf(EUR_FR::class, Currencies::getInstance()->getLocaleByID('EUR_FR'));
        $this->assertInstanceOf(EUR_FR::class, Currencies::getInstance()->getLocaleByID('eur_fr'));
    }

    public function test_resolveLocaleToDefault() : void
    {
        $this->assertInstanceOf(EUR_EU::class, Currencies::getInstance()->getLocale('EUR'));
    }

    public function test_resolveLocaleByID() : void
    {
        $this->assertInstanceOf(EUR_FR::class, Currencies::getInstance()->getLocale('EUR_FR'));
    }

    public function test_resolveLocaleByInstance() : void
    {
        $locale = Currencies::getInstance()->getLocaleByID('EUR_FR');

        $this->assertSame($locale, Currencies::getInstance()->getLocale($locale));
    }
}
