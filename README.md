# Currency parser

PHP library that can detect prices written in text or markup, and normalise 
their formatting according to country-specific rules.

## Requirements

- PHP 7.4+
- [Composer](https://getcomposer.org)

## Examples of recognized formats

Primarily intended to parse prices written by humans, the parser is quite
tolerant and will recognize any of these notations (and more) - either as
standalone text or scattered in a larger document.

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

> * Based on the assumption that prices always have
> 1-2 decimal places.

## Usage

### Detecting specific currencies

To be able to detect prices, the parser needs to know what kind of currencies
to expect in the document. In the following example, we only use Euro prices. 

```php
use Mistralys\CurrencyParser\PriceParser;

$subject = 'Price of a basic subscription: 50,42 €.';

$prices = PriceParser::create()
    ->expectCurrency('EUR')
    ->findPrices($subject);
```

### Detecting all currencies

For performance reasons, it is best to limit the list currencies to search for
in a document. If this cannot be determined reliably, you may use all of them:

```php
use Mistralys\CurrencyParser\PriceParser;

$subject = '(document with prices here)';

$prices = PriceParser::create()
    ->expectAnyCurrency()
    ->findPrices($subject);
```

> Also see the next section on how to handle currencies that share the same
> currency symbol.

### Multiple possible currencies per symbol 

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
    
echo $prices[0]->getCurrencyName(); // USD
echo $prices[1]->getCurrencyName(); // USD
```

The parser will not be able to tell which of those two prices are the CAD and 
USD ones. By default, the **parser will use `USD` in case of conflict with the
`$` symbol**. Using currency names does not have this issue:

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

echo $prices[0]->getCurrencyName(); // CAD
echo $prices[1]->getCurrencyName(); // USD
```

### Setting default currencies per symbol

The parser has built-in defaults for currency symbol conflicts, like `USD` for `$`.
However, this can be adjusted if the target currency used in the document is known. 
Consider the following example:

```php
use Mistralys\CurrencyParser\PriceParser;

$subject = <<<EOT
Starting price: $35
Black Friday rebate: $9.99
Your price: $25.01
EOT;

$prices = PriceParser::create()
    ->expectCurrency('CAD')
    ->setSymbolDefault('$', 'CAD')
    ->findPrices($subject);

echo $prices[0]->getCurrencyName(); // CAD
echo $prices[1]->getCurrencyName(); // CAD
echo $prices[3]->getCurrencyName(); // CAD
```

> If the document uses multiple currencies with the same symbol, this will not make 
> it possible to distinguish between them. Only using currency names can solve such 
> cases.

## Philosophy

This library does not attempt to reproduce every possible variation of how
prices are written internationally. It is intended to smooth out some
of the stranger quirks to stay consistent while staying true to the countries'
most notable idiosyncrasies.

> NOTE: The library does not currently aim to include all worldwide
> currencies, but welcomes any contributions.
