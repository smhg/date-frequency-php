<?php
namespace Frequency;

define('STRING_VALIDATION', '/^F((\d+|\(\w+\))[YMWD](?:\/[EYMW])?)*(?:T((\d+|\(\w+\))[HMS](?:\/[EYMWDH])?)*)?$/');

define('RULE_PARSER', '/(\d+|\(\w+\))([YMWDHS])(?:\/([EYMWDH]))?/');

class Frequency
{
    public static $fn = array();

    protected $rules = array();

    public function __construct($str = null)
    {
        $rules = array();

        if (is_string($str)) {
            if (!preg_match(STRING_VALIDATION, $str)) {
                throw new Exception('Invalid frequency \'' . $str . '\'');
            }

            $parts = preg_split('/T(?![^(]*\))/', $str);

            $addRule = function ($value, $unit, $scope = null) use (&$rules) {
                if (!($scope = Scope::filter($unit, $scope))) {
                    return;
                }

                $scopes = isset($rules[$unit]) ? $rules[$unit] : array();

                if (!$value) {
                    $value = Unit::$defaults[$unit];
                } elseif (substr($value, 0, 1) === '(') {
                    $value = substr($value, 1, count($value) - 2);
                } else {
                    $value = (int)$value;
                }

                $scopes[$scope] = $value;

                $rules[$unit] = $scopes;
            };

            $result = array();

            preg_match_all(RULE_PARSER, $parts[0], $matches, PREG_SET_ORDER);
            foreach($matches as $rule) {
                $addRule($rule[1], $rule[2], isset($rule[3]) ? $rule[3] : null);
            }

            if (isset($parts[1])) {
                preg_match_all(RULE_PARSER, $parts[1], $matches, PREG_SET_ORDER);
                foreach($matches as $rule) {
                    $addRule($rule[1], strtolower($rule[2]));
                }
            }
        } elseif (is_array($str)) {
            $rules = $str;
        }

        foreach ($rules as $unit => $rule) {
            foreach ($rule as $scope => $value) {
                $this->on($unit, $value, $scope);
            }
        }
    }

    public function on($unit, $value, $scope = null)
    {
        $unit = Unit::filter($unit);

        if (!$unit) {
            throw new Exception('Invalid unit');
        }

        $scope = Scope::filter($unit, Unit::filter($scope));

        $rule = array($scope => $value);

        if (!is_numeric($value)) {
            if (!isset(Frequency::$fn[$value])) {
              throw new Exception(sprintf('Filter function \'%s\' not available', $value));
            }
        }


        $this->rules[$unit] = isset($this->rules[$unit]) ? $this->rules[$unit] : array();

        $this->rules[$unit] = array_merge($this->rules[$unit], $rule);

        return $this;
    }

    public function getValue($unit, $scope = null)
    {
        $rules = $this->rules;
        $unit = Unit::filter($unit);
        $scope = Scope::filter($unit, Unit::filter($scope));

        if (!isset($rules[$unit])) {
            return;
        }

        if (!isset($rules[$unit][$scope])) {
            return;
        }

        return $rules[$unit][$scope];
    }

    public function next(\DateTime $date)
    {
        $date = clone $date;
        $rules = $this->rules;
        $fixedUnits = array_keys(array_filter($rules, function($rule) {
                return isset($rule['fix']);
            }));

        $scopes = array_combine(array_keys(Unit::$defaults), array_map(function ($u) use ($rules) {
                if (isset($rules[$u]) && isset($rules[$u]['scope'])) {
                    return $rules[$u]['scope'];
                }
                return Scope::getDefault($u);
            }, array_keys(Unit::$defaults)));

        $resetUnit = function ($parent = null) use (&$date, $scopes) {
                // parent = optional parent unit below which we are doing the reset
                return function ($u) use (&$date, $scopes, $parent) {
                    if (isset($scopes[$u])) {
                        $full = array_search($u, Unit::$full);
                        if ($parent && Unit::compare($scopes[$u], $parent) === -1) {
                            $date->modify('-' . (Unit::get($date, $u, $parent) - Unit::$defaults[$u]) . ' ' . $full);
                        } else {
                            $date->modify('-' . (Unit::get($date, $u, $scopes[$u]) - Unit::$defaults[$u]) . ' ' . $full);
                        }
                    }
                };
            };

        $filter = function ($date, $unit, $rule = null) use ($resetUnit) {
                if ($rule) {
                    $fn = Frequency::$fn[$rule['fn']];
                    $full = array_search($unit, Unit::$full);

                    $success = $fn(Unit::get($date, $unit, $rule['scope']), $date);

                    if (!$success) {
                        do {
                            $date->modify('+1 ' . $full);
                        } while (!$fn(Unit::get($date, $unit, $rule['scope']), $date));

                        return true;
                    }
                }

                return false;
            };

        foreach (Unit::$order as $unit) {
            if (isset($rules[$unit])) {
                $rule = $rules[$unit];
                if (isset($rule['fix'])) {
                    $datePart = Unit::get($date, $unit, $rule['scope']);
                    $full = array_search($unit, Unit::$full);

                    if ($datePart < $rule['fix']) {
                        $date->modify('+' . ($rule['fix'] - $datePart) . ' ' . $full);

                        // reset everything below current unit
                        $lowerUnits = Unit::lower($unit);
                        array_walk($lowerUnits, $resetUnit($unit));
                    } else if ($datePart > $rule['fix']) {
                        // find closest non fixed parent
                        $scopesAbove = array_diff(array_intersect_key($scopes, array_flip(array_merge(Unit::higher($unit), array($unit)))), $fixedUnits);
                        end($scopesAbove);
                        $parent = current($scopesAbove);
                        $parentUnit = key($scopesAbove);

                        // raise that parent
                        $date->modify('+1 ' . array_search($parent, Unit::$full));
                        if (isset($rules[$parent]) && isset($rules[$parent]['fn'])) {
                            $filter($date, $parent, $rules[$parent]);
                        }

                        // reset everything below that parent and above the current unit (except for fixed values)
                        $reset = array_merge(array_diff(Unit::between($parentUnit, $unit), $fixedUnits), array($unit), Unit::lower($unit));
                        array_walk($reset, $resetUnit());

                        $date->modify('+' . ($rule['fix'] - Unit::$defaults[$unit]) . ' ' . $full);
                    }
                } elseif (isset($rule['fn'])) {
                    $filterChangedSomething = $filter($date, $unit, $rule);

                    if ($filterChangedSomething) {
                        $lowerUnits = Unit::lower($unit);
                        array_walk($lowerUnits, $resetUnit($unit));
                    }
                }
            }
        }

        return $date;
    }

    public function between(\DateTime $start, \DateTime $end)
    {
        $result = array();
        $d = clone $start;

        $d = $this->next($d);
        while ($d < $end) {
            $result[] = clone $d;
            $d->modify('+1 second');
            $d = $this->next($d);
        }

        return $result;
    }

    public function __clone()
    {
        $frequency = new Frequency();
        $frequency->rules = $this->rules;
        return $frequency;
    }

    public function __toString()
    {
        $result = 'F';
        $hasTime = false;

        foreach (Unit::$order as $unit) {
            if (isset($this->rules[$unit])) {
                $rule = $this->rules[$unit];

                if (!$hasTime && in_array($unit, array('h', 'm', 's'))) {
                    $result .= 'T';
                    $hasTime = true;
                }

                foreach (Scope::$scopes[$unit] as $scope) {
                    if (isset($rule[$scope])) {
                        $value = $rule[$scope];

                        if (is_numeric($value)) {
                            $result .= $value;
                        } else {
                            $result .= sprintf('(%s)', $value);
                        }

                        $result .= strtoupper($unit);

                        if ($scope !== Scope::getDefault($unit)) {
                            $result .= '/' . $scope;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
