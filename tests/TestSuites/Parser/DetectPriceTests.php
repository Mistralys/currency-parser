<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Parser;

use Mistralys\CurrencyParser\Currencies;
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

    public function test_parseTestString() : void
    {
        //$this->enableDebug();

        $result = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findPrices($this->testString);

        $expected = array(
            50.00,
            -33.00,
            1000.00,
            42.12,
            15.00,
            500.00,
            9.00
        );

        $this->assertCount(7, $result);

        foreach ($result as $idx => $match)
        {
            $this->assertSame(
                $expected[$idx],
                $match->getAsFloat(),
                $match->getMatchedString()
            );
        }
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

    public function test_multiplePricesWithName() : void
    {
        $subject = <<<EOT
Canadian dollars: CAD1,000
U.S. dollars: USD1,000
EOT;

        //$this->enableDebug();

        $prices = $this->createTestParser()
            ->expectCurrency('USD')
            ->expectCurrency('CAD')
            ->findPrices($subject);

        $this->assertCount(2, $prices);
        $this->assertSame('CAD', $prices[0]->getCurrency()->getName());
        $this->assertSame('USD', $prices[1]->getCurrency()->getName());
    }

    /**
     * The parser must select USD as default for dollar prices
     * when using the symbol, even if other currencies have been
     * added to the expected pool.
     *
     * This is handled by the property {@see PriceParser::$symbolDefaults},
     * which is passed on to {@see Currencies::autoDetect()}.
     */
    public function test_multiplePricesWithSymbol() : void
    {
        $subject = <<<EOT
Canadian dollars: $1,000
U.S. dollars: $1,000
EOT;

        //$this->enableDebug();

        $prices = $this->createTestParser()
            ->expectCurrency('CAD')
            ->expectCurrency('USD')
            ->findPrices($subject);

        $this->assertCount(2, $prices);
        $this->assertSame('USD', $prices[0]->getCurrency()->getName());
    }

    public function test_parseSupportedFormatsExample() : void
    {
        //$this->enableDebug();

        $string = <<<'EOT'
- `$1000`
- `$1,000.00` _With thousands separators_ 
- `$ 1 , 000 . 00` _Free-spacing, including newlines_
- `$1.000,00` _Separator style agnostic_
- `$1.000.00` _Yes, really*_
- `$1000.2` _1 to 2 decimal places_
- `1000 EUR` _Currency symbols or names_
- `EUR 1000` _Symbol placement agnostic_
- `-$ 1000` _Minus before symbol_
- `$ -1000` _Minus after symbol_
- `50,- €` _German short style decimals_
- `1 000,00 € TTC` _French style with VAT_
EOT;

        $result = $this
            ->createTestParser()
            ->expectAnyCurrency()
            ->findPrices($string);

        $expected = array(
            1 => array(
                'currency' => 'USD',
                'float' => 1000.00
            ),
            2 => array(
                'currency' => 'USD',
                'float' => 1000.00
            ),
            3 => array(
                'currency' => 'USD',
                'float' => 1000.00
            ),
            4 => array(
                'currency' => 'USD',
                'float' => 1000.00
            ),
            5 => array(
                'currency' => 'USD',
                'float' => 1000.00
            ),
            6 => array(
                'currency' => 'USD',
                'float' => 1000.20
            ),
            7 => array(
                'currency' => 'EUR',
                'float' => 1000.00
            ),
            8 => array(
                'currency' => 'EUR',
                'float' => 1000.00
            ),
            9 => array(
                'currency' => 'USD',
                'float' => -1000.00
            ),
            10 => array(
                'currency' => 'USD',
                'float' => -1000.00
            ),
            11 => array(
                'currency' => 'EUR',
                'float' => 50.00
            ),
            12 => array(
                'currency' => 'EUR',
                'float' => 1000.00
            )
        );

        $this->assertCount(count($expected), $result);

        foreach ($result as $idx => $match)
        {
            $test = $expected[$idx+1];
            $label = 'Match [#'.($idx+1).']: '.$match->getMatchedString();

            $this->assertSame(
                $test['float'],
                $match->getAsFloat(),
                $label
            );

            $this->assertSame(
                $test['currency'],
                $match->getCurrency()->getName(),
                $label
            );
        }
    }

    public function test_parseDefaultSymbolExample() : void
    {
        $subject = <<<EOT
Starting price: $35
Black Friday rebate: $9.99
Your price: $25.01
EOT;

        $prices = PriceParser::create()
            ->expectCurrency('CAD')
            ->setSymbolDefault('$', 'CAD')
            ->findPrices($subject);

        $this->assertCount(3, $prices);

        $this->assertSame($prices[0]->getCurrencyName(), 'CAD');
        $this->assertSame($prices[1]->getCurrencyName(), 'CAD');
        $this->assertSame($prices[2]->getCurrencyName(), 'CAD');
    }

    // endregion

    // region: Support methods

    private string $testString = <<<'EOT'
450 
1,45 
50 €
-€33
1000,00 EUR 
42.12€ 
15,-EUR
EUR 500 TTC
€9
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
