<?php
namespace Frequency;

class Unit
{
    public static $order = array('Y', 'M', 'D', 'h', 'm', 's');

    public static $full = array(
        'year' => 'Y',
        'month' => 'M',
        'week' => 'W',
        'day' => 'D',
        'hour' => 'h',
        'minute' => 'm',
        'second' => 's'
    );

    public static $defaults = array(
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

    public static function lower($unit)
    {
        return array_slice(self::$order, array_search($unit, self::$order) + 1);
    }

    public static function higher($unit)
    {
        return array_slice(self::$order, 0, array_search($unit, self::$order));
    }

    public static function between($left, $right)
    {
        return array_intersect(self::lower($left), self::higher($right));
    }

    public static function get(\DateTime $date, $unit, $scope)
    {
        switch ($unit) {
            case 'M':
                switch ($scope) {
                    case 'Y':
                        return (int)$date->format('n');
                        break;
                }
                break;
            case 'W':
                switch ($scope) {
                    case 'Y':
                        return (int)$date->format('W');
                        break;
                }
                break;
            case 'D':
                switch ($scope) {
                    case 'Y':
                        return (int)$date->format('z');
                        break;
                    case 'M':
                        return (int)$date->format('j');
                        break;
                    case 'W':
                        return (int)$date->format('N');
                        break;
                }
                break;
            case 'h':
                switch ($scope) {
                    case 'D':
                        return (int)$date->format('G');
                        break;
                }
                break;
            case 'm':
                switch ($scope) {
                    case 'h':
                        return (int)$date->format('i');
                        break;
                }
                break;
            case 's':
                switch ($scope) {
                    case 'm':
                        return (int)$date->format('s');
                        break;
                }
                break;
        }

        throw new Exception('Invalid unit/scope');
    }
}
