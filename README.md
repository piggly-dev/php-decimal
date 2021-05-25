# An arbitrary-precision Decimal class type for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/piggly/php-decimal.svg?style=flat-square)](https://packagist.org/packages/piggly/php-decimal) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE) 

This library is similar to [decimal.js](https://github.com/MikeMcl/decimal.js/), all business logic credits and copyrights go to [Michael Mclaughlin](https://github.com/MikeMcl) and yours [contributors](https://github.com/MikeMcl/decimal.js/graphs/contributors).

The `piggly/php-decimal` is fully compatible with PHP and so fast as **decimal.js** library. See below all features attached to it.

## Features

This library is mainly composed by `Decimal` class. A `Decimal` is composed by coefficients, exponent and sign. It can handle integer and float values with an arbitrary-precision. 

Here precision is specified in terms of significant digits rather than decimal places, and all calculations are rounded to the precision (similar to Python's decimal module) rather than just those involving division.

This library also adds the trigonometric functions, among others, and supports non-integer powers, which makes it a significantly larger library.

* Integers and floats;
* Simple but full-featured API;
* Replicates many of the native math methods;
* Also handles hexadecimal, binary and octal values;
* Faster and easier to use than another PHP libraries;
* No dependencies or requirements;
* Comprehensive documentation and test set.

## Usage

`Decimal` can handle integers, floats, strings and `Decimal` objects:

```php
$w = new Decimal(123);
$x = new Decimal(123.4567);
$y = new Decimal('123456.7e-3');
$z = new Decimal($x);
```

A value can also be in binary, hexadecimal or octal if the appropriate prefix is included:

```php
$w = new Decimal('0xff.f');		// '255.9375'
$x = new Decimal('0b10101100');	// '172'
```

A `Decimal` is immutable in the sense that it is not changed by its methods, always returning a new `object`.

```php
$x = new Decimal(0.3);

$x->minus(0.1);						// $x is still 0.3
$y = $x->minus(0.1)->minus(0.1);	// $x is still 0.3 and $y is 0.1
```

All methods which returns Decimal values can be chained.

```php
$x->dividedBy($y)->plus($z)->times(9)->floor();
$x->times('1.23456780123456789e+9')->plus(9876.5432321)->dividedBy('4444562598.111772')->ceil();
```

Many method names have a shorter alias.

```php
$x->squareRoot()->dividedBy($y)->toPower(3)->equals($x->sqrt()->div($y)->pow(3))	// true
$x->cmp($y->mod($z)->neg()) == 1 && x->comparedTo($y->modulo($z)->negated()) == 1	// true
```

There are many ways to convert a `Decimal` to an string,

```php
$x = new Decimal(255.5);

$x->toExponential(5)  // '2.55500e+2'
$x->toFixed(5)        // '255.50000'
$x->toPrecision(5)    // '255.50'
$x->valueOf()         // '255.5'
```

and almost all methods are available as `static`.

```php
Decimal::sqrtOf('6.98372465832e+9823')      // '8.3568682281821340204e+4911'
Decimal::powOf(2, 0.0979843)                // '1.0702770511687781839'
```

`Decimal` can handle with `INF` and `NAN` values.

```php
$x = new Decimal(INF);     // INF
$y = new Decimal(NAN);     // NAN
```

There are many methods to do any checks to `Decimal`.

```php
$x->isCountless()    // If it is INF or NAN
$x->isFinite()       // If it is a finite number
$x->isInfinite()     // If it is an infinite number
$x->isInt()          // If it is an integer
$x->isNaN()          // If it is NAN
$x->isNegative()     // If it is negative
$x->isNulled()       // If it is INF, NAN or Zero
$x->isPositive()     // If it is positive
$x->isZero()         // If it is zero
```

By the way, there is a `toFraction` method with an optional maximum denominator argument.

```php
$z = new Decimal(355);
$pi = $z->dividedBy(113);        // '3->1415929204'
$pi->toFraction();               // [ '7853982301', '2500000000' ]
$pi->toFraction(1000);           // [ '355', '113' ]
```

All calculations are rounded according to the number of significant digits and rounding mode specified by the `precision` and `rounding` properties of the `DecimalConfig` object.

Each `Decimal` class is associated to a `DecimalConfig`. It may be the `global` configuration, or a custom configuration to that specific decimal number.

For advanced usage, multiple `Decimal` can be created, each with their own independent configuration which applies to all `Decimal` numbers created from it.

```php
// Set the precision and rounding of the global instance, 
// applies to all Decimal objects without configurations attached to it.
DecimalConfig::instance()->set([ 'precision' => 5, 'rounding' => 4 ]);

$decimal9 = DecimalConfig::clone()->set([ 'precision' => 9, 'rounding' => 1 ]);

$x = new Decimal(5);
$y = new Decimal(5, $decimal9);

$x->div(3);     // '1.6667'
$y->div(3);     // '1.66666666'

// $decimal9 applies to all `Decimal` numbers 
// created from $y in this case
$y->div(3)->times(1.5) // '2.50000000'
```

The value of a `Decimal` object is stored in a floating point format in terms of its `digits`, `exponent` and `sign`.

```php
$x = new Decimal(-12345.67);

$x->getDigits();          // [ 12345, 6700000 ]    digits (base 10000000)
$x->getExponent();        // 4                     exponent (base 10)
$x->getSign();            // -1                    sign
```

For further information see the [API](docs/api.md) reference in the docs directory, for now you may go to [decimal.js API](https://mikemcl.github.io/decimal.js/) since this library is fully compatible with it.

## Installation

### Composer

1. At you console, in your project folder, type `composer require piggly/php-decimal`;
2. Don't forget to add Composer's autoload file at your code base `require_once('vendor/autoload.php);`.

### Manual install

1. Download or clone with repository with `git clone https://github.com/piggly-dev/php-decimal.git`;
2. After, goes to `cd /path/to/piggly/php-decimal`;
3. Install all Composer's dependencies with `composer install`;
4. Add project's autoload file at your code base `require_once('/path/to/piggly/php-decimal/vendor/autoload.php);`.

## Dependencies 

The library has the following external dependencies:

* PHP 7.3+.

## TODO

In code, there annotations `@todo` with some improvements that this library may need.

## Changelog

See the [CHANGELOG](CHANGELOG.md) file for information about all code changes.

## Testing the code

This library uses the PHPUnit. We carry out tests of all the main classes of this application.

```bash
vendor/bin/phpunit
```

> You must always run tests with PHP 7.3 and greater. Any changes at this library need to pass of all oldest and newests tests.

> **!!** Some tests are heavy, be careful while testing them, they may require huge memory available.

## Contributions

See the [CONTRIBUTING](CONTRIBUTING.md) file for information before submitting your contribution.

## Credits

- [Caique Araujo](https://github.com/caiquearaujo);
- [All contributors](../../contributors);

### [decimal.js](https://github.com/MikeMcl/decimal.js/)

- [Michael Mclaughlin at decimal.js](https://github.com/MikeMcl);
- [Contributors at decimal.js](https://github.com/MikeMcl/decimal.js/graphs/contributors).

## Support the project

Piggly Studio is an agency located in Rio de Janeiro, Brazil. If you like this library and want to support this job, be free to donate any value to BTC wallet `3DNssbspq7dURaVQH6yBoYwW3PhsNs8dnK` ❤.

## License

MIT License (MIT). See [LICENSE](LICENSE).