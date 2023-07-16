<?php
namespace Frequency;

use \DateTime;
use \DateTimeInterface;

$day = 24 * 60 * 60;
define('DAY', $day);

$week = DAY * 7;
define('WEEK', $week);

function weekOfEpoch (DateTimeInterface $date) {
    $epoch = new DateTime('1969-12-29T00:00:00', $date->getTimezone());
    return floor(($date->format('U') - $epoch->format('U') + $epoch->format('Z') - $date->format('Z')) / WEEK);
}

class Unit
{
    public static $order = array('Y', 'M', 'W', 'D', 'h', 'm', 's');

    public static $full = array(
        'epoch' => 'E',
        'year' => 'Y',
        'month' => 'M',
        'week' => 'W',
        'day' => 'D',
        'hour' => 'h',
        'minute' => 'm',
        'second' => 's'
    );

    public static $defaults = array(
        'Y' => 0,
        'M' => 1,
        'D' => 1,
        'h' => 0,
        'm' => 0,
        's' => 0
    );

    public static function filter($unit)
    {
        if (isset(self::$full[$unit])) {
            return self::$full[$unit];
        }

        if (in_array($unit, self::$full)) {
            return $unit;
        }

        return;
    }

    public static function compare($left, $right)
    {
        $leftPos = array_search($left, self::$order);
        $rightPos = array_search($right, self::$order);

        if ($leftPos < $rightPos) {
          return -1;
        } else if ($leftPos === $rightPos) {
          return 0;
        }

        return 1;
    }

    public static function lower($unit)
    {
        $pos = array_search($unit, self::$order);

        if ($pos === false) {
            throw new Exception('Invalid base unit');
        }

        return array_slice(self::$order, $pos + 1);
    }

    public static function higher($unit)
    {
        $pos = array_search($unit, self::$order);

        if ($pos === false) {
            throw new Exception('Invalid base unit');
        }

        return array_slice(self::$order, 0, $pos);
    }

    public static function between($left, $right)
    {
        return array_intersect(self::lower($left), self::higher($right));
    }

    public static function get(DateTimeInterface $date, $unit, $scope = null)
    {
        switch ($unit) {
            case 'Y':
                return (int)$date->format('Y');
            case 'M':
                switch ($scope) {
                    case 'E':
                        throw new Exception('Scope not implemented: month of epoch');
                    case 'Y':
                        return (int)$date->format('n');
                }
                break;
            case 'W':
                switch ($scope) {
                    case 'E':
                        return (int)weekOfEpoch($date);
                    case 'Y':
                        return (int)$date->format('W');
                    case 'M':
                        throw new Exception('Scope not implemented: week of month');
                }
                break;
            case 'D':
                switch ($scope) {
                    case 'E':
                        throw new Exception('Scope not implemented: day of epoch');
                    case 'Y':
                        return (int)$date->format('z') + 1;
                    case 'W':
                        return (int)$date->format('N');
                    case 'M':
                        return (int)$date->format('j');
                }
                break;
            case 'h':
                switch ($scope) {
                    case 'D':
                        return (int)$date->format('G');
                }
                break;
            case 'm':
                switch ($scope) {
                    case 'h':
                        return (int)$date->format('i');
                }
                break;
            case 's':
                switch ($scope) {
                    case 'm':
                        return (int)$date->format('s');
                }
                break;
        }

        throw new Exception('Invalid unit/scope: ' . $unit . '/' . $scope);
    }
}
