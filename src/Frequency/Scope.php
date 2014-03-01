<?php
namespace Frequency;

class Scope
{
    public static $scopes = array(
        'M' => 'Y',
        'W' => 'Y',
        'D' => array('M', 'Y', 'W'),
        'h' => 'D',
        'm' => 'h',
        's' => 'm'
    );

    public static function filter($unit, $scope)
    {
        if (!isset(self::$scopes[$unit])) {
            throw new Exception('Invalid unit');
        }

        if (self::$scopes[$unit] === $scope
            || (is_array(self::$scopes[$unit]) && in_array($scope, self::$scopes[$unit]))) {
                return $scope;
        } else {
            self::getDefault($unit);
        }
    }

    public static function getDefault($unit)
    {
        if (is_array(self::$scopes[$unit])) {
            return self::$scopes[$unit][0];
        }

        return self::$scopes[$unit];
    }
}
