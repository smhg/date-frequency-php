date-frequency-php [![Build status](https://api.travis-ci.org/smhg/date-frequency-php.png)](https://travis-ci.org/smhg/date-frequency-php)
==================

PHP temporal frequency library

Should match functionality of [JavaScript version](https://github.com/smhg/date-frequency-js).

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

#### on(string unit, int fix, [string scope])
Add a rule (fixed value) to the frequency for a unit, optionally linked to a scope.

#### next(DateTime start)
Get the next occurence of the frequency on or after a date.

#### between(DateTime start, DateTime end)
Get all occurences of the frequency between 2 dates.
