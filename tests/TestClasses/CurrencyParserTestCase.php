<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestClasses;

use Mistralys\CurrencyParser\PriceParser;
use PHPUnit\Framework\TestCase;

abstract class CurrencyParserTestCase extends TestCase
{
    private bool $debug = false;

    public function enableDebug() : void
    {
        $this->debug = true;
    }

    public function createTestParser(): PriceParser
    {
        return PriceParser::create()
            ->setDebugEnabled($this->debug);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->debug = false;
    }
}
