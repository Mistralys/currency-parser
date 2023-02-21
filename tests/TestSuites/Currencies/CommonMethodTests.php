<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Currencies;

use Mistralys\CurrencyParser\Currencies;
use Mistralys\CurrencyParser\Currencies\EUR;
use Mistralys\CurrencyParser\Currencies\EUR\EUR_FR;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class CommonMethodTests extends CurrencyParserTestCase
{
    public function test_getByName() : void
    {
        $this->assertInstanceOf(EUR::class, Currencies::getInstance()->getByName('EUR'));
        $this->assertInstanceOf(EUR::class, Currencies::getInstance()->getByName('eur'));
    }

    public function test_getFormatByID() : void
    {
        $this->assertInstanceOf(EUR_FR::class, Currencies::getInstance()->getLocaleByID('EUR_FR'));
        $this->assertInstanceOf(EUR_FR::class, Currencies::getInstance()->getLocaleByID('eur_fr'));
    }
}
