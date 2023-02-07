<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Parser;

use Mistralys\CurrencyParser\PriceParser;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class DetectPriceTests extends CurrencyParserTestCase
{


    // region: _Tests

    /**
     * To be able to detect prices, currencies must be added
     * to the expected currencies list.
     */
    public function test_noExpectedCurrenciesAdded() : void
    {
        $this->expectExceptionCode(PriceParser::ERROR_NO_EXPECTED_CURRENCIES_SET);

        $this->createTestParser()->findPrices('Subject');
    }

    public function test_parse() : void
    {
        //$this->enableDebug();

        $result = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findPrices($this->testString);

        $this->assertCount(5, $result);
        $this->assertSame(50.00, $result[0]->getAsFloat());
        $this->assertSame(1000.00, $result[1]->getAsFloat());
        $this->assertSame(42.12, $result[2]->getAsFloat());
        $this->assertSame(15.00, $result[3]->getAsFloat());
        $this->assertSame(500.00, $result[4]->getAsFloat(), $result[4]->getMatchedString());
    }

    public function test_limit() : void
    {
        $this->assertCount(
            2,
            $this
                ->createTestParser()
                ->expectCurrency('EUR')
                ->findPrices($this->testString, 2)
        );
    }

    public function test_frenchVAT() : void
    {
        $price = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findFirstPrice('42 EUR TTC');

        $this->assertNotNull($price);
        $this->assertSame('TTC', $price->getVAT());
    }

    public function test_frenchVATCaseInsensitive() : void
    {
        //$this->enableDebug();

        $price = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findFirstPrice('42 eur ht');

        $this->assertNotNull($price);
        $this->assertSame('HT', $price->getVAT());
    }

    public function test_multiplePrices() : void
    {
        $subject = <<<EOT
Canadian dollars: CAD1,000
U.S. dollars: USD1,000
EOT;

        $prices = PriceParser::create()
            ->expectCurrency('USD')
            ->expectCurrency('CAD')
            ->findPrices($subject);

        $this->assertCount(2, $prices);
        $this->assertSame('CAD', $prices[0]->getCurrency()->getName());
        $this->assertSame('USD', $prices[1]->getCurrency()->getName());
    }

    // endregion

    // region: Support methods

    private string $testString = <<<'EOT'
450 
1,45 
50 €
1000,00 EUR 
42.12€ 
15,-EUR
EUR 500 TTC
EOT;

    private bool $debug = false;

    private function enableDebug() : void
    {
        $this->debug = true;
    }

    private function createTestParser(): PriceParser
    {
        return PriceParser::create()
            ->setDebugEnabled($this->debug);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->debug = false;
    }

    // endregion
}
