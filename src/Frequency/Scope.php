<?php
namespace Frequency;

class Scope
{
    public static $scopes = array(
        'Y' => array('E'),
        'M' => array('Y', 'E'),
        'W' => array('Y', 'M', 'E'),
        'D' => array('M', 'W', 'Y', 'E'),
        'h' => array('D', 'W', 'M', 'Y', 'E'),
        'm' => array('h', 'D', 'W', 'M', 'Y', 'E'),
        's' => array('m', 'h', 'D', 'W', 'M', 'Y', 'E')
    );

    public static function filter($unit, $scope)
    {
        if (!isset(self::$scopes[$unit])) {
            throw new Exception('Invalid unit');
        }

        if (is_array(self::$scopes[$unit])) {
            if (!in_array($scope, self::$scopes[$unit])) {
                return self::getDefault($unit);
            }
        } elseif (self::$scopes[$unit] !== $scope) {
            return self::getDefault($unit);
        }

        return $scope;
    }

    public static function getDefault($unit)
    {
        if (is_array(self::$scopes[$unit])) {
            return self::$scopes[$unit][0];
        }

        return self::$scopes[$unit];
    }
}
