# Currency parser

PHP library that can detect prices written in text or markup, and normalise 
their formatting according to country-specific rules.

## Requirements

- PHP 7.4+
- [Composer](https://getcomposer.org)

## Usage

### Detecting prices

To be able to detect prices, the parser needs to know what kind of currencies
to expect in the document. In the following example, we only use Euro prices. 

```php
use Mistralys\CurrencyParser\PriceParser;

$subject = 'Some text or HTML markup with a price of 50,42 €.';

$prices = PriceParser::create()
    ->expectCurrency('EUR')
    ->findPrices($subject);
```

### Detecting currencies with the same symbol

It is possible to add multiple currencies to the parser. However, it will not 
be able to tell them apart if they share the same symbol. Consider the following
text:

```php
use Mistralys\CurrencyParser\PriceParser;

$subject = <<<EOT
Canadian dollars: $1,000
U.S. dollars: $1,000
EOT;

$prices = PriceParser::create()
    ->expectCurrency('USD')
    ->expectCurrency('CAD')
    ->findPrices($subject);
```

The parser will not be able to tell which of those two prices are the CAD and 
USD ones. A solution is to use currency names instead of symbols if possible.
This will work without problems:

```php
use Mistralys\CurrencyParser\PriceParser;

$subject = <<<EOT
Canadian dollars: CAD1,000
U.S. dollars: USD1,000
EOT;

$prices = PriceParser::create()
    ->expectCurrency('USD')
    ->expectCurrency('CAD')
    ->findPrices($subject);
```

## Philosophy

This library does not attempt to reproduce every possible variation of how
prices are written internationally. It is intended to smooth out some
of the stranger quirks to stay consistent while staying true to the countries'
most notable idiosyncrasies.

> NOTE: The library does not currently aim to include all worldwide
> currencies, but welcomes any contributions.
