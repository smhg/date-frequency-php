<?php
namespace Frequency;

use \DateTime;
use \DateTimeImmutable;

define('STRING_VALIDATION', '/^F((\d+|\(\w+\))[YMWD](?:\/[EYMW])?)*(?:T((\d+|\(\w+\))[HMS](?:\/[EYMWDH])?)*)?$/');

define('RULE_PARSER', '/(\d+|\(\w+\))([YMWDHS])(?:\/([EYMWDH]))?/');

class Frequency
{
    public static $fn = array();
    public static $MAX_ATTEMPTS = 100;

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
                $scope = Scope::filter($unit, $scope);

                if (!$scope) {
                    return;
                }

                $scopes = isset($rules[$unit]) ? $rules[$unit] : array();

                if (!$value) {
                    $value = Unit::$defaults[$unit];
                } elseif (substr($value, 0, 1) === '(') {
                    $value = substr($value, 1, strlen($value) - 2);
                } else {
                    $value = (int)$value;
                }

                $scopes[$scope] = $value;

                $rules[$unit] = $scopes;
            };

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

    public function next(DateTime|DateTimeImmutable $date = null) {
        $rules = $this->rules;

        if (!$date) {
            $date = new DateTime();
        } else {
            $date = clone $date;
        }

        // result should never contain microseconds, so round down
        $date->modify(sprintf('-%s microsecond', $date->format('u')));

        for ($i = count(Unit::$order) - 1; $i >= 0; $i--) {
            $unit = Unit::$order[$i];

            if (!isset($rules[$unit])) {
                continue;
            }

            $rule = $rules[$unit];
            $scopes = array_values(array_filter(Scope::$scopes[$unit], function ($scope) use ($rule) {
                return isset($rule[$scope]);
            }));

            $safety = 0;
            $scopeCount = count($scopes);
            for ($j = 0; $j < $scopeCount; $j++) {
                if (++$safety > Frequency::$MAX_ATTEMPTS) {
                    throw new Exception(sprintf(
                        'Gave up after %d to find a match for %s.',
                        Frequency::$MAX_ATTEMPTS,
                        $unit
                    ));
                }

                $scope = $scopes[$j];
                $ruleValue = $rule[$scope];
                $dateValue = Unit::get($date, $unit, $scope);

                if (is_numeric($ruleValue)) {
                    if ($ruleValue === $dateValue) {
                        continue;
                    }

                    if ($dateValue < $ruleValue) {
                        $full = array_search($unit, Unit::$full);
                        $date->modify(sprintf('+%d %s', $ruleValue - $dateValue, $full));

                        $lowerUnits = array_filter(Unit::lower($unit), function ($unit) use($rules) {
                            return isset(Unit::$defaults[$unit]) && !isset($rules[$unit]);
                        });

                        foreach($lowerUnits as $lowerUnit) {
                            $dv = Unit::get($date, $lowerUnit, Scope::getDefault($lowerUnit));
                            $def = Unit::$defaults[$lowerUnit];

                            if ($dv !== $def) {
                                $full = array_search($lowerUnit, Unit::$full);
                                $date->modify(sprintf('-%d %s', $dv - $def, $full));
                            }
                        }

                        continue;
                    }

                    if ($dateValue > $ruleValue) {
                        $full = array_search($unit, Unit::$full);
                        $date->modify(sprintf('-%d %s', $dateValue - $ruleValue, $full));
                        $scopeFull = array_search($scope, Unit::$full);
                        $date->modify(sprintf('+1 %s', $scopeFull));

                        $lowerUnits = array_filter(Unit::lower($unit), function ($unit) use($rules) {
                            return isset(Unit::$defaults[$unit]) && !isset($rules[$unit]);
                        });

                        foreach($lowerUnits as $lowerUnit) {
                            $dv = Unit::get($date, $lowerUnit, Scope::getDefault($lowerUnit));
                            $def = Unit::$defaults[$lowerUnit];

                            if ($dv !== $def) {
                                $full = array_search($lowerUnit, Unit::$full);
                                $date->modify(sprintf('-%d %s', $dv - $def, $full));
                            }
                        }

                        $j = -1;

                        continue;
                    }
                } else {
                    $fn = Frequency::$fn[$ruleValue];
                    $full = array_search($unit, Unit::$full);

                    $success = $fn(Unit::get($date, $unit, $scope), $date);

                    if (!$success) {
                        do {
                            $date->modify(sprintf('+1 %s', $full));
                        } while (!$fn(Unit::get($date, $unit, $scope), $date));

                        $j = -1; // check all scopes of this unit again
                    }
                }
            }
        }

        return $date;
    }

    public function between(DateTime|DateTimeImmutable $start, DateTime|DateTimeImmutable $end)
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
