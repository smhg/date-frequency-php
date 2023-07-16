<?php
namespace Frequency;

class Scope
{
    /**
     * @var array<string, array<string>>
     */
    public static array $scopes = array(
        'Y' => array('E'),
        'M' => array('Y', 'E'),
        'W' => array('Y', 'M', 'E'),
        'D' => array('M', 'Y', 'W', 'E'),
        'h' => array('D'),
        'm' => array('h'),
        's' => array('m')
    );

    public static function filter(string $unit, ?string $scope = null): string
    {
        if (!array_key_exists($unit, self::$scopes)) {
            throw new Exception('Invalid unit');
        }

        if (is_null($scope) || !in_array($scope, self::$scopes[$unit])) {
            return self::getDefault($unit);
        }

        return $scope;
    }

    public static function getDefault(string $unit): string
    {
        if (!array_key_exists($unit, self::$scopes)) {
            throw new Exception('Invalid unit');
        }

        return self::$scopes[$unit][0];
    }
}
