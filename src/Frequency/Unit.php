<?php
namespace Frequency;

use \DateTime;
use \DateTimeInterface;

$day = 24 * 60 * 60;
define('DAY', $day);

$week = DAY * 7;
define('WEEK', $week);

function weekOfEpoch (DateTimeInterface $date): float
{
    $epoch = new DateTime('1969-12-29T00:00:00', $date->getTimezone());
    return floor(($date->format('U') - $epoch->format('U') + $epoch->format('Z') - $date->format('Z')) / WEEK);
}

class Unit
{
    /**
     * @var array<int, string>
     */
    public static array $order = array('Y', 'M', 'W', 'D', 'h', 'm', 's');

    /**
     * @var array<string, string>
     */
    public static array $full = array(
        'epoch' => 'E',
        'year' => 'Y',
        'month' => 'M',
        'week' => 'W',
        'day' => 'D',
        'hour' => 'h',
        'minute' => 'm',
        'second' => 's'
    );

    /**
     * @var array<string, int>
     */
    public static array $defaults = array(
        'Y' => 0,
        'M' => 1,
        'D' => 1,
        'h' => 0,
        'm' => 0,
        's' => 0
    );

    public static function filter(?string $unit): string|null
    {
        if (array_key_exists($unit, self::$full)) {
            return self::$full[$unit];
        }

        if (in_array($unit, self::$full)) {
            return $unit;
        }

        return null;
    }

    /**
     * @return int<-1,1>
     */
    public static function compare(string $left, string $right): int
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

    /**
     * @return array<string>
     */
    public static function lower(string $unit): array
    {
        $pos = array_search($unit, self::$order);

        if ($pos === false) {
            throw new Exception('Invalid base unit');
        }

        return array_slice(self::$order, $pos + 1);
    }

    /**
     * @return array<string>
     */
    public static function higher(string $unit): array
    {
        $pos = array_search($unit, self::$order);

        if ($pos === false) {
            throw new Exception('Invalid base unit');
        }

        return array_slice(self::$order, 0, $pos);
    }

    /**
     * @return array<string>
     */
    public static function between(string $left, string $right): array
    {
        return array_intersect(self::lower($left), self::higher($right));
    }

    public static function get(DateTimeInterface $date, string $unit, ?string $scope = null): int
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
