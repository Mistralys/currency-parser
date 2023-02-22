<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Formatter;

use Mistralys\CurrencyParser\PriceMatch;

class IndividualCustomFormatter extends BaseCustomFormatter
{
    public function __construct(PriceMatch $price)
    {
        parent::__construct();

        $this->workPrice = $price;
    }

    public function getPrice() : PriceMatch
    {
        return $this->workPrice;
    }

    public function format(): string
    {
        return $this->_format($this->workPrice);
    }
}
