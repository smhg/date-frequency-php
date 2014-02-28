<?php
namespace Frequency;

class Frequency
{
    protected $_rules;

    public function __construct($str)
    {

    }

    public function getValue($unit, $scope)
    {
        return $this->_rules[$unit][$scope];
    }
}
