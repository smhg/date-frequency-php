date-frequency-php [![Build status](https://api.travis-ci.org/smhg/date-frequency-php.png)](https://travis-ci.org/smhg/date-frequency-php)
==================

> Temporal frequency library

PHP clone of [JavaScript version](https://github.com/smhg/date-frequency-js).

## Installation
```bash
$ composer require smhg/date-frequency
```

## Methods
### Frequency
```php
use Frequency\Frequency;

$frequency = new Frequency();
$frequency->on('day', 3, 'week')
	->on('hour', 10)
	->on('minute', 0)
	->on('seconds', 0); // every Wednesday at 10:00:00
```

#### Frequency([string rules])
#### Frequency([array rules])
Pass rules as a string (see `__toString`) or an array to the constructor instead of setting them one-by-one with `on()`.

Example above as a string: `$frequency = new Frequency('F3D/WT10H0M0S');`

#### on(string unit, int fix, [string scope])
#### on(string unit, array options)
Add a rule (fixed value) to the frequency for a unit, optionally linked to a scope.

The options array can contain the keys `fix`, `scope` and `fn` (filter function).
Filter functions need to be available in the static `Frequency::$fn` array when used.

Example filter: `$frequency = new Frequency('F(leap)Y1M1DT0H0M0S');` (Jan 1st, at midnight, of leap years)

See tests for more examples.

#### next(DateTime start)
Get the next occurence of the frequency on or after a date.

#### between(DateTime start, DateTime end)
Get all occurences of the frequency between 2 dates.

#### __toString()
Convert frequency to a string value.
