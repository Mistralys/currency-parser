<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Locales;

use Mistralys\CurrencyParser\PriceFormatter;
use Mistralys\CurrencyParser\PriceParser;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class CountrySpecificTests extends CurrencyParserTestCase
{
    /**
     * Verify that the international preferred formatting styles
     * correspond to the defined formats.
     */
    public function test_international() : void
    {

        $tests = array(
            array(
                'locale' => 'EUR_DE',
                'number' => '-1000.00 €',
                'expected' => '-1.000,00[SPACE]€'
            ),
            array(
                'locale' => 'EUR_AT',
                'number' => '-1000.00 €',
                'expected' => '-1.000,00[SPACE]€'
            ),
            array(
                'locale' => 'EUR_IT',
                'number' => '-1000.00 €',
                'expected' => '-1.000,00[SPACE]€'
            ),
            array(
                'locale' => 'EUR_ES',
                'number' => '-1000.00 €',
                'expected' => '-1.000,00[SPACE]€'
            ),
            array(
                'locale' => 'EUR_FR',
                'number' => '-1000.00 €',
                'expected' => '-[SPACE]1[SPACE]000,00[SPACE]€'
            ),
            array(
                'locale' => 'USD',
                'number' => '-$1000.00',
                'expected' => '-$1,000.00'
            ),
            array(
                'locale' => 'MXN',
                'number' => '-$1000.00',
                'expected' => 'MXN[SPACE]-1,000.00'
            ),
            array(
                'locale' => 'CAD',
                'number' => '-$1000.00',
                'expected' => '$[SPACE]-1,000.00'
            ),
            array(
                'locale' => 'GBP',
                'number' => '-£1000.00',
                'expected' => '-£1,000.00'
            )
        );

        //$this->enableDebug();

        foreach($tests as $test)
        {
            $price = PriceParser::create()
                ->expectCurrency($test['locale'])
                ->setDebugEnabled($this->isDebugEnabled())
                ->findPrices($test['number'])
                ->getFirst();

            $this->assertNotNull($price, $test['locale']);

            $result = PriceFormatter::createLocale($test['locale'])
                ->setSymbolModePreferred()
                ->format($price);

            $this->assertSame($test['expected'], str_replace(' ', '[SPACE]', $result), $test['locale']);
        }
    }

    public function test_de_es_it() : void
    {
        $countries = array('DE', 'ES', 'IT');

        foreach($countries as $countryID)
        {
            $tests = array(
                array(
                    'label' => 'Whole number',
                    'text' => '50 EUR',
                    'expected' => '50[SPACE]EUR'
                ),
                array(
                    'label' => 'Without notation space',
                    'text' => '50EUR',
                    'expected' => '50[SPACE]EUR'
                ),
                array(
                    'label' => 'With minus sign',
                    'text' => '-50 EUR',
                    'expected' => '-50[SPACE]EUR'
                ),
                array(
                    'label' => 'With euro sign',
                    'text' => '50€',
                    'expected' => '50[SPACE]€'
                ),
                array(
                    'label' => 'With one decimal',
                    'text' => '50,1 €',
                    'expected' => '50,1[SPACE]€'
                ),
                array(
                    'label' => 'With two decimals',
                    'text' => '50,10 €',
                    'expected' => '50,10[SPACE]€'
                ),
                array(
                    'label' => 'With dot decimal separator',
                    'text' => '50.55 €',
                    'expected' => '50,55[SPACE]€'
                ),
                array(
                    'label' => 'With german style hyphen',
                    'text' => '50,- €',
                    'expected' => '50,-[SPACE]€'
                ),
                array(
                    'label' => 'With wild spaces',
                    'text' => '  -    50      EUR  ',
                    'expected' => '  -50[SPACE]EUR  '
                ),
                array(
                    'label' => 'Thousands, no decimals',
                    'text' => '100000 EUR',
                    'expected' => '100.000[SPACE]EUR'
                ),
                array(
                    'label' => 'Thousands with separator, no decimals',
                    'text' => '100,000 EUR',
                    'expected' => '100.000[SPACE]EUR'
                ),
                array(
                    'label' => 'Thousands with separator, with decimals',
                    'text' => '100,000.42 EUR',
                    'expected' => '100.000,42[SPACE]EUR'
                ),
                array(
                    'label' => 'Thousands with separator, with decimals, wrong separators',
                    'text' => '100.000.42 EUR',
                    'expected' => '100.000,42[SPACE]EUR'
                ),
                array(
                    'label' => 'Thousands with spaces and decimals',
                    'text' => '100 000,42 EUR',
                    'expected' => '100.000,42[SPACE]EUR'
                ),
                array(
                    'label' => 'Thousand with spaces and decimals',
                    'text' => '1 000,42 EUR',
                    'expected' => '1.000,42[SPACE]EUR'
                ),
                array(
                    'label' => 'Thousand with spaces no decimals',
                    'text' => '1 000 EUR',
                    'expected' => '1.000[SPACE]EUR'
                )
            );

            foreach ($tests as $test)
            {
                $price = PriceParser::create()
                    ->setDebugEnabled($this->isDebugEnabled())
                    ->expectCurrency('EUR_'.$countryID)
                    ->findPrices($test['text'])
                    ->getFirst();

                $this->assertNotNull($price, $test['label']);

                $result = $price->formatHTML();

                $this->assertEquals(
                    str_replace('[SPACE]', '&#160;', $test['expected']),
                    $result,
                    $test['label']
                );
            }
        }
    }
}
