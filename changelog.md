## v1.1.0 - Mailcode and Localization update (Breaking-L)
- Localization: Updated references to classes refactored in AppLocalize.
- Dependencies: Updated AppLocalize to [v1.5.0](https://github.com/Mistralys/application-localization/releases/tag/1.5.0).
- Dependencies: Updated Mailcode to [v3.5.0](https://github.com/Mistralys/mailcode/releases/tag/3.5.0).

### Breaking changes

- Currency locale: `getCountry()` now returns `CountryInterface`.
- Currencies: `getLocaleByCountry()` now requires a `CountryInterface` instance.
- Price filters: `setFormatterByCountry()` now requires a `CountryInterface` instance.
- Breaking changes in AppLocalize (see [changelog](https://github.com/Mistralys/application-localization/releases/tag/1.5.0)).
- Breaking changes in Mailcode (see [changelog](https://github.com/Mistralys/mailcode/releases/tag/3.5.0)).

## v1.0.11 - Support non-standard spaces
- Merged pull request [#5](https://github.com/Mistralys/currency-parser/pull/5), thanks @daun!
- Parser: Prices formatted with non-standard spaces are now recognized.

## v1.0.10 - Mailcode update
- Dependencies: Updated Mailcode to [v3.2.0](https://github.com/Mistralys/mailcode/releases/tag/3.2.0).

## v1.0.9 - Mailcode compatibility
- Parser: Fixed special case where a Mailcode placeholder ends with a dot.

## v1.0.8 - Fixed EUR HTML entity
- Parser: Fixed wrong HTML entity number for the Euro symbol.

## v1.0.7 - Fixed leftover debug output
- Parser: Fixed a stray `print_r` not being tied to the debug setting (thanks @timsassen!).

## v1.0.6 - Euro name fix
- Parser: Now ignoring the written "Euro" currency name.
- Added reasoning behind ignoring "Euro" in the readme.

## v1.0.5 - Mailcode compatibility
- Parser: Fixed Mailcode placeholders recognized as prices ([#2](https://github.com/Mistralys/currency-parser/issues/2)).

## v1.0.4 - Bugfix release
- Parser: Fixed false positives detected in some cases ([#1](https://github.com/Mistralys/currency-parser/issues/1)).

## v1.0.3 - Bugfix release
- Parser: Fixed standalone currency names causing PHP notices.

## v1.0.2 - Bugfix release
- Parser: Fixed price detection in some edge cases.
- Parser: Fixed not detecting a currency when the default symbol currency is not in the expected list.
- Tests: Added expected international formatting verification.
- Tests: Added PHPStan configuration and batch files.
- Code: Fixed PHPStan analysis recommendations.

## v1.0.1 - Bugfix release
- Parser: Fixed trailing whitespace being stripped from prices.

## v1.0.0 - Initial release
- Initial feature-set release.
