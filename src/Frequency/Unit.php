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
}
