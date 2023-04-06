# Currency parser

PHP library that can detect prices written in text or markup, adding 
non-breaking spaces, and normalising their formatting according to 
country-specific rules.

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
- `$1.000.00` _Same separator mistake*_
- `$1000.2` _1 to 2 decimal places_
- `1000 EUR` _Currency symbols or names_
- `EUR 1000` _Symbol placement agnostic_
- `-$ 1000` _Minus before symbol_
- `$ -1000` _Minus after symbol_
- `50,- €` _German short style decimals_
- `1 000,00 € TTC` _French style with VAT_
- `.50 €` _Decimals only_

> * Based on the assumption that prices always have
> 1-2 decimal places, this part of the number is 
> assumed to be the decimals.

### _Euro_ exception

The _Euro_ is the only one of the supported currencies whose name begins
with the same letters as its currency name, `EUR`. The parser will 
ignore prices written like this:

```
"...and he gave him 42 Euro for his troubles."
```

The reasoning being that using this name is ambiguous in a pure currency
context. It is typically used when mentioning prices in body text, where
price formatting is not necessary or even expected. 

## Quick Start

### Get a list of prices in a text or markup

```php
use function Mistralys\CurrencyParser\findPrices;

$subject = <<<EOT
Base price: $1,000.00
Your price: $860.00
EOT;

$prices = findPrices($subject, 'USD');

foreach($prices as $price)
{
    // do something with them
}
```

### Format a single price string

Using auto-detection for all supported currencies:

```php
use function Mistralys\CurrencyParser\parsePrice;

echo parsePrice('$1000.00')->formatText();
```

Output:
```
$1,000.00
```

With a specific currency locale for precise formatting when
the currency is used in several countries, like the Euro:

```php
use function Mistralys\CurrencyParser\parsePrice;

echo parsePrice('1000.00 €', 'EUR_DE')->formatText();
```

Output:
```
1.000,00 €
```

### Format all prices in a text or markup

The following will automatically format prices according to 
the selected currencies' default locale.

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT' 
Starting price: 1000.00 $
Special price: € 860.00
EOT;

echo PriceFilter::createForLocales('USD', 'EUR')
    ->filterString($subject);
```

Output:
```
Starting price: $1,000.00
Special price: 860.00 €
```

To be more precise, the currency locale can be provided.
For example, Euros are formatted slightly differently in
France than in the rest of Europe.

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT' 
Prix de départ: EUR 1000.00
Prix spécial: EUR 860.00
EOT;

echo PriceFilter::createForLocales('EUR_FR')
    ->filterString($subject);
```

Output:
```
Prix de départ: 1 000,00 EUR
Prix spécial: 860,00 EUR
```

### Add non-breaking spaces to prices

When formatting prices, spaces automatically get replaced by non-breaking
space characters for use in text documents. This can be easily switched to 
an HTML context:

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT' 
Starting price: 1000.00 $
Special price: 860.00 $
EOT;

echo PriceFilter::createForLocales('USD')
    ->setNonBreakingSpaceHTML()
    ->filterString($subject)
```

Output:
```
Prix de départ: 1 000,00 EUR
Prix spécial: 860,00 EUR
```

### Change the currency symbol    

By default, the currency filter makes no changes to the currency symbols
used in the document. This means that a mixed symbol usage will remain the
same even after formatting the numbers.

#### Change to currency symbols

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT'
With name: USD 1000
With symbol: $ 1000
EOT;

echo PriceFilter::createForLocales('USD')
    ->setSymbolModeSymbol()
    ->filterString($subject);
```

Output:
```
With name: $1,000
With symbol: $1,000
```

#### Change to currency names

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT'
With name: USD 1000
With symbol: $ 1000
EOT;

echo PriceFilter::createForLocales('USD')
    ->setSymbolModeSymbol()
    ->filterString($subject);
```

Output:
```
With name: $1,000
With symbol: $1,000
```

#### Change to country preferred style

For the Euro, the preferred style is to use the currency symbol
for prices instead of the name. 

```php
use Mistralys\CurrencyParser\PriceFilter;

$subject = <<<'EOT'
With name: EUR 1000
With symbol: € 1000
EOT;

echo PriceFilter::createForLocales('EUR_FR')
    ->setSymbolModePreferred()
    ->filterString($subject);
```

Output:
```
With name: € 1 000
With symbol: € 1 000
```

## Formatter usage

### What is the formatter?

The formatter is used to format individual prices found in a text by the parser. 
It knows how to format prices according to the bundled currency locales, like 
American or Mexican Dollars, or French Euros. It can also be customised to format 
prices any way you like.

> NOTE: To format multiple prices at once, look at the Price Filter.

### Locale-based formatting

Fire-and-forget formatting that uses the currency locale definitions.

```php
use Mistralys\CurrencyParser\PriceFormatter;
use function Mistralys\CurrencyParser\parsePrice;

echo PriceFormatter::createLocale('USD')
    ->format(parsePrice('$ 1000'))
```

A locale formatter does not allow changing formatting details like the decimal
separator. Only the symbol mode and space character can be adjusted:

```php
use Mistralys\CurrencyParser\PriceFormatter;
use function Mistralys\CurrencyParser\parsePrice;

echo PriceFormatter::createLocale('USD')
    ->setNonBreakingSpaceHTML()
    ->setSymbolModeName()
    ->format(parsePrice('$ 1000'))
```

### Custom formatting

Using a custom formatter, all formatting details can can be freely adjusted.

```php
use Mistralys\CurrencyParser\PriceFormatter;
use function Mistralys\CurrencyParser\parsePrice;

$formatter = PriceFormatter::createCustom()
    ->setDecimalSeparator('[DECIMAL]')
    ->setThousandsSeparator('[THOUSAND]')
    ->setArithmeticSeparator('[ARITHMETIC]')
    ->setNonBreakingSpace('[SPACE]')
    ->setSymbolPosition(PriceFormatter::SYMBOL_POSITION_END)
    ->setSymbolSpaceAtTheEnd(PriceFormatter::SPACE_BEFORE);

echo $formatter->formatPrice(parsePrice('$ -1000.00'));
```

Output:
```
-[ARITHMETIC]1[THOUSAND]000[DECIMAL]00[SPACE]$
```

> NOTE: A formatter instance can be re-used as necessary.

### Custom formatter based on a locale

Let's say that we wish to use the default USD formatting, but instead of placing
the symbol at the beginning (default behavior), we want to display it at the end.

We have to use a custom formatter for this, but we can use an existing locale
formatter to fill out the default settings. All that's left to do then is overwrite
the relevant settings.

```php
use Mistralys\CurrencyParser\PriceFormatter;

$formatter = PriceFormatter::createCustom()
    ->configureWithLocale(\Mistralys\CurrencyParser\currencyLocale('USD'))
    ->setSymbolPositionAtTheEnd();
```

## Filter usage

### What is the price filter?

The Filter is used to format multiple prices in text or markup documents,
with a minimum of code, and leaving the rest of the document intact. 

### Filtering a text document

The fire and forget version of filtering a document is to specify what kind
of currencies to expect, and let the filter handle all the details based on
how the currency is typically formatted.

```php
use Mistralys\CurrencyParser\PriceFilter;

$formatted = PriceFilter::createForLocales('USD')
    ->filterString($subject);
```

### Filtering an HTML document

This works exactly like a text document, except that the non-breaking space
character is adjusted to use the HTML style (which uses an HTML entity instead
of the actual character).

```php
use Mistralys\CurrencyParser\PriceFilter;

$formatted = PriceFilter::createForLocales('USD')
    ->setNonBreakingSpaceHTML()
    ->filterString($subject);
```

### Using custom formatters

To use a custom formatter for a currency instead of the locale-based one, the
formatter instance must be set separately.

```php
use Mistralys\CurrencyParser\PriceFilter;
use Mistralys\CurrencyParser\PriceFormatter;

// Configure a custom formatter
$customFormatter = PriceFormatter::createCustom()
    ->setDecimalSeparator(' ')
    ->setThousandsSeparator(',')
    ->setSymbolPositionBeforeMinus()
    ->setSymbolModeName();

$formatted = PriceFilter::create()
    ->setFormatter('USD', $customFormatter)
    ->filterString($subject);
```

Custom formatters and locale formatters can be freely combined. In the example
above, we used `PriceFilter::create()`, because all formatters were custom. 
Here, we use  a default locale formatter for french prices, and a custom one
for U.S. dollars:

```php
use Mistralys\CurrencyParser\PriceFilter;
use Mistralys\CurrencyParser\PriceFormatter;

// Configure a custom formatter
$customFormatter = PriceFormatter::createCustom()
    ->setDecimalSeparator(' ')
    ->setThousandsSeparator(',')
    ->setSymbolPositionBeforeMinus()
    ->setSymbolModeName();

$formatted = PriceFilter::createForLocales('EUR_FR')
    ->setFormatter('USD', $customFormatter)
    ->filterString($subject);
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

## Mailcode compatibility

The [Mailcode](https://github.com/Mistralys/mailcode) command preprocessor uses numbers
as placeholders for commands when safeguarding them to apply formatting filters (more
on the [reasoning behind this](https://github.com/Mistralys/mailcode#avoiding-delimiter-conflicts)). 
This can cause placeholders of variable commands to be falsely recognized as prices.

Example text with a variable command:

```
Price from a variable: {showvar: $FOO.PRICE} EUR
```

The `showvar` command is converted to a numeric placeholder by the safeguard feature:

```
Price from a variable: 9990000000001999 EUR
```

To avoid this being formatted into a price (and thus breaking the Mailcode command),
simply enable the Mailcode support in the currency parser via the `expectMailcode()`
method:

```php
use Mailcode\Mailcode;
use Mistralys\CurrencyParser\PriceParser;
use Mistralys\CurrencyParser\PriceFilter;

$text = 'Price from a variable: {showvar: $FOO.PRICE} EUR';

// Replace Mailcode commands with placeholders
$safeguard = Mailcode::create()->createSafeguard($text);
$safeText = $safeguard->makeSafe();

$currencyParser = PriceParser::create()
    ->expectCurrency('EUR')
    ->expectMailcode();

// Format all currencies in the text
$formattedText = PriceFilter::create($currencyParser)
    ->filterString($safeText);

// Restore the Mailcode commands
$filteredText = $safeguard->makeWhole($formattedText);
```

> NOTE: This will only work with the default Mailcode placeholder delimiters. 
> Using a custom delimiter is not supported.

## Handling multi-currency documents

In documents with multiple currencies, if they use the same symbol (USD and CAD
for example), one must be set as default.


## Philosophy

There is an excellent money library for PHP, [Money](https://github.com/moneyphp/money). 
This library does not attempt to reproduce the same functionality - it was developed
for an application in particular, which requires the formatting to be fully 
whitespace-aware. The methodology in general is focused on the filtering aspect, 
whereas Money is a full-fledged financial calculation tool. 

The two libraries can be used together: Prices have a method to get their value in
money integer style.

```php
// (int)5000
$money = \Mistralys\CurrencyParser\parsePrice('$50')->getAsMoney();
```

> NOTE: The built-int locale-based formatting may vary slightly from a library like 
> Money. This is due to the formatting rules defined in the application for which the
> library was developed.

### Contributing

Contributions are always welcome. The library does not currently aim to include all 
worldwide currencies, but we are open tp any you may be able to add via pull requests. 
Look in the [Currencies](https://github.com/Mistralys/currency-parser/tree/main/src/Currencies)
folder to get an overview of what's there.
