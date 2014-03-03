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

    public static function get(\DateTime $date, $unit, $scope = null)
    {
        switch ($unit) {
            case 'M':
                switch ($scope) {
                    default: // Y
                        return (int)$date->format('n');
                        break;
                }
                break;
            case 'W':
                switch ($scope) {
                    default: // Y
                        return (int)$date->format('W');
                        break;
                }
                break;
            case 'D':
                switch ($scope) {
                    case 'Y':
                        return (int)$date->format('z');
                        break;
                    case 'W':
                        return (int)$date->format('N');
                        break;
                    default: // M
                        return (int)$date->format('j');
                        break;
                }
                break;
            case 'h':
                switch ($scope) {
                    default: // D
                        return (int)$date->format('G');
                        break;
                }
                break;
            case 'm':
                switch ($scope) {
                    default: // h
                        return (int)$date->format('i');
                        break;
                }
                break;
            case 's':
                switch ($scope) {
                    default: // m
                        return (int)$date->format('s');
                        break;
                }
                break;
        }

        throw new Exception('Invalid unit/scope');
    }
}
