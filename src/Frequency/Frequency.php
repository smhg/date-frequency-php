<?php
namespace Frequency;

class Frequency
{
    public static $fn = array();

    protected $rules = array();

    public function __construct($str = null)
    {
        $rules = array();

        if (is_string($str)) {
            $units = array('Y', 'M', 'W', 'D', 'h', 'm', 's');
            $pattern = implode('', array(
                    '/^F',
                    '(?:(\\d+|\\(\\w*\\))Y(?:\\/([E]{1}))?)?',
                    '(?:(\\d+|\\(\\w*\\))M(?:\\/([EY]{1}))?)?',
                    '(?:(\\d+|\\(\\w*\\))W(?:\\/([EYM]{1}))?)?',
                    '(?:(\\d+|\\(\\w*\\))D(?:\\/([EYMW]{1}))?)?',
                    '(?:T',
                    '(?:(\\d+|\\(\\w*\\))H(?:\\/([EYMWD]{1}))?)?',
                    '(?:(\\d+|\\(\\w*\\))M(?:\\/([EYMWDH]{1}))?)?',
                    '(?:(\\d+|\\(\\w*\\))S(?:\\/([EYMWDHM]{1}))?)?',
                    ')?$/'
                ));

            $matches = array();
            if (!preg_match_all($pattern, $str, $matches)) {
                throw new Exception('Invalid frequency \'' . $str . '\'');
            }

            array_shift($matches);
            $length = count($matches);
            for ($i = 0;$i < count($matches);$i += 2) {
                if (strlen($matches[$i][0]) > 0) {
                    $u = $units[$i / 2];

                    $rules[$u] = array();

                    if (substr($matches[$i][0], 0, 1) === '(') {
                        $rules[$u]['fn'] = trim($matches[$i][0], ' ()');
                    } else {
                        $rules[$u]['fix'] = (int)$matches[$i][0];
                    }

                    if ($matches[$i + 1][0]) {
                        $rules[$u]['scope'] = $matches[$i + 1][0];
                    }
                }
            }
        } elseif (is_array($str)) {
            $rules = $str;
        }

        foreach ($rules as $unit => $rule) {
            $this->on($unit, $rule);
        }
    }

    public function on($unit, $options = null)
    {
        $unit = Unit::filter($unit);

        if (!$unit) {
            throw new Exception('Invalid unit');
        }

        if (is_numeric($options)) {
            // second parameter = fix
            $options = array(
                'fix' => $options
            );
            if (func_num_args() === 3) {
                $options['scope'] = func_get_arg(2);
            }
        }

        $rule = array(
                'scope' => Scope::filter($unit, Unit::filter(isset($options['scope']) ? $options['scope'] : null))
            );

        if (isset($options['fn'])) {
            if (!isset(Frequency::$fn[$options['fn']])) {
              throw new Exception(sprintf('Filter function \'%s\' not available', $options['fn']));
            }

            $rule['fn'] = $options['fn'];
        } else {
            $rule['fix'] = isset($options['fix']) ? $options['fix'] : Unit::$defaults[$unit];
        }

        $this->rules[$unit] = $rule;

        return $this;
    }

    public function getValue($unit, $scope = null)
    {
        $rules = $this->rules;
        $unit = Unit::filter($unit);
        $scope = Scope::filter($unit, Unit::filter($scope));

        if (!isset($rules[$unit]) || $rules[$unit]['scope'] !== $scope) {
            return;
        }

        $rule = $rules[$unit];

        if (isset($rule['fix'])) {
            return $rule['fix'];
        } else if (isset($rule['fn'])) {
            return $rule['fn'];
        }
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

                        $lowerUnits = Unit::lower($unit);
                        array_walk($lowerUnits, $resetUnit($unit));
                    }
                }
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
                    $filter($date, $unit, $rule);
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

                if (isset($rule['fix'])) {
                    $result .= $rule['fix'];
                } else {
                    $result .= sprintf('(%s)', $rule['fn']);
                }

                $result .= strtoupper($unit);

                if ($rule['scope'] !== Scope::getDefault($unit)) {
                    $result .= '/' . $rule['scope'];
                }
            }
        }

        return $result;
    }
}
