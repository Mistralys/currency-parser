### v1.0.5 - Mailcode compatibility
- Parser: Fixed Mailcode placeholders recognized as prices ([#2](https://github.com/Mistralys/currency-parser/issues/2)).

### v1.0.4 - Bugfix release
- Parser: Fixed false positives detected in some cases ([#1](https://github.com/Mistralys/currency-parser/issues/1)).

### v1.0.3 - Bugfix release
- Parser: Fixed standalone currency names causing PHP notices.

### v1.0.2 - Bugfix release
- Parser: Fixed price detection in some edge cases.
- Parser: Fixed not detecting a currency when the default symbol currency is not in the expected list.
- Tests: Added expected international formatting verification.
- Tests: Added PHPStan configuration and batch files.
- Code: Fixed PHPStan analysis recommendations.

### v1.0.1 - Bugfix release
- Parser: Fixed trailing whitespace being stripped from prices.

### v1.0.0 - Initial release
- Initial feature-set release.
