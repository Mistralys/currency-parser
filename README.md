# Currency parser

PHP library that can detect prices written in text or markup, and normalise 
their formatting according to country-specific rules.

## Requirements

- PHP 7.4+
- [Composer](https://getcomposer.org)

## Installation

Use Composer to add the library to your project:

```
composer require mistralys/currency-parser
```

Alternatively, clone it locally using the GIT command line (or [GitHub Desktop](https://desktop.github.com/)),
or manually [download a release](https://github.com/Mistralys/currency-parser/releases).

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

## Quick Start

### Get a list of prices in a text or markup

```php
use function Mistralys\CurrencyParser\findPrices;

$subject = <<<EOT
Base price: $1,000.00
Your price: $860.00
EOT;

$prices = findPrices($subject);

foreach($prices as $price)
{
    // do something with them
}
```

### Format a single price string

```php
use function Mistralys\CurrencyParser\parsePrice;

echo parsePrice('$1000.00')->format();
```

Output:
```
$1,000.00
```

### Format all prices in a text or markup

The following will automatically detect currencies,
and format them according to the default currency locale.
For USD, there is only a single locale, so confusion
is impossible.

```php
use function Mistralys\CurrencyParser\filterString;

$subject = <<<'EOT' 
Starting price: 1000.00 $
Special price: 860.00 $
EOT;

echo filterString($subject);
```

Output:
```
Starting price: $1,000.00
Special price: $860.00
```

To be more precise, the currency locale can be provided.
For example, Euros are formatted slightly differently in
France than in the rest of Europe.

```php
use function Mistralys\CurrencyParser\filterString;

$subject = <<<'EOT' 
Prix de départ: EUR 1000.00
Prix spécial: EUR 860.00
EOT;

echo filterString($subject);
```

Output:
```
Prix de départ: 1 000,00 EUR
Prix spécial: 860,00 EUR
```

### Change the currency symbol style    

By default, the currency filter makes no changes to the currency symbols
used in the document. This means that a mixed symbol usage will remain the
same even after formatting the numbers.

To change this, three options are available:

- Change all to use currency symbols (`$`).
- Change all to use currency names (`USD`).
- Change all to use the currency's preferred style.

Example with Euros: The preferred style is to use the currency symbol for
prices instead of the name. 

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT'
With name: EUR 1000
With symbol: € 1000
EOT;

// Custom filter configuration, providing a currency locale
echo PriceFilter::createForLocales('EUR_FR')
    ->setSymbolModePreferred()
    ->filterString($subject);
```

Output:
```
With name: € 1 000
With symbol: € 1 000
```

## Formatter usage

### What is the formatter?

The formatter is used to format prices found in a text by the parser. It knows 
how to format prices according to the bundled currency locales, like American
or Mexican Dollars, or French Euros. It can also be customised to format prices 
any way you like.

### Formatting by locale



```php
use Mistralys\CurrencyParser\PriceFormatter;
use Mistralys\CurrencyParser\PriceParser;

// The formatter instance can be re-used as necessary
$formatter = PriceFormatter::createForLocale('USD');

// Get a price instance
$price = PriceParser::create()
    ->expectCurrency('USD')
    ->findPrices('$1000.00')
    ->getFirst();

$formatted = $formatter->formatPrice($price);


```

## Parser usage

### What is the parser?

The parser is able to find prices written in arbitrary texts, including
within markup (HTML or XML). It can be used as a standalone utility to access
price information, to do with as you please.

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

There is an excellent money library for PHP, [Money](https://github.com/moneyphp/money). 
This library does not attempt to reproduce the same functionality - it was developed
for an application in particular, which requires the formatting to be fully 
whitespace-aware. The methodology in general is focused on the filtering aspect, 
whereas Money is a full-fledged financial calculation tool. 

The two libraries can be used together: Prices have a method to get their value in
money integer style.

```php
\Mistralys\CurrencyParser\parsePrice('$50')->getAsMoney();
```

> NOTE: The built-int locale-based formatting may vary slightly from a library like 
> Money. This is due to the formatting rules defined in the application for which the
> library was developed.

### Contributing

Contributions are always welcome. The library does not currently aim to include all 
worldwide currencies, but we are open tp any you may be able to add via pull requests. 
Look in the [Currencies](https://github.com/Mistralys/currency-parser/tree/main/src/Currencies)
folder to get an overview of what's there.
