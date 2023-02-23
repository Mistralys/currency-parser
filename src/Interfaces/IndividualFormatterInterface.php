<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

interface IndividualFormatterInterface extends FormatterInterface
{
    public function format() : string;
}
