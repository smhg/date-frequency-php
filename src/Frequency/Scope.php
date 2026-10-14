<?php
namespace Frequency;

class Scope
{
    /**
     * @var array<string, array<string>>
     */
    public static array $scopes = array(
        'Y' => array('E'),
        'M' => array('Y'),
        'W' => array('Y', 'E'),
        'D' => array('M', 'Y', 'W'),
        'h' => array('D'),
        'm' => array('h'),
        's' => array('m')
    );

    public static function filter(string $unit, ?string $scope = null): string
    {
        if (!array_key_exists($unit, self::$scopes)) {
            throw new Exception('Invalid unit');
        }

        if (is_null($scope)) {
            return self::getDefault($unit);
        }

        $filtered = Unit::filter($scope);

        if (is_null($filtered) || !in_array($filtered, self::$scopes[$unit])) {
            throw new Exception(sprintf('Invalid scope \'%s\' for unit %s', $scope, $unit));
        }

        return $filtered;
    }

    public static function getDefault(string $unit): string
    {
        if (!array_key_exists($unit, self::$scopes)) {
            throw new Exception('Invalid unit');
        }

        return self::$scopes[$unit][0];
    }
}
