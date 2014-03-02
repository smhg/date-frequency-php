<?php
namespace Frequency;

class Frequency
{
    protected $rules = array();

    public function __construct($str = null)
    {
        $rules = array();

        if (is_string($str)) {
            $units = array('M', 'D', 'h', 'm', 's');
            $pattern = implode('', array(
                '/^F',
                '(?:(\\d+)M(?:\\/([Y]{1}))?)?',
                '(?:(\\d+)D(?:\\/([YMW]{1}))?)?',
                '(?:T',
                '(?:(\\d+)H(?:\\/([YMWD]{1}))?)?',
                '(?:(\\d+)M(?:\\/([YMWDH]{1}))?)?',
                '(?:(\\d+)S(?:\\/([YMWDHM]{1}))?)?',
                ')?$/'
            ));

            $matches = array();
            if (!preg_match_all($pattern, $str, $matches)) {
                throw new Exception('Invalid frequency \'' . $str . '\'');
            }

            array_shift($matches);
            $length = count($matches);
            for ($i = 0;$i < count($matches);$i += 2) {
                $u = $units[$i / 2];

                $rules[$u] = array(
                    'fix' => (int)$matches[$i][0]
                );

                if ($matches[$i + 1][0]) {
                    $rules[$u]['scope'] = $matches[$i + 1][0];
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

        if (!$unit || $unit === 'Y') {
            // rules on first unit (year) are not possible
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

        if (!$options['fix']) {
            $options['fix'] = Unit::$defaults[$unit];
        }

        $options['scope'] = Scope::filter($unit, Unit::filter(isset($options['scope']) ? $options['scope'] : null));

        $this->rules[$unit] = $options;

        return $this;
    }

    public function getValue($unit, $scope = null)
    {
        $unit = Unit::filter($unit);
        $scope = Scope::filter($unit, Unit::filter($scope));

        if (!$this->rules[$unit] || $this->rules[$unit]['scope'] !== $scope) {
            return;
        }

        return $this->rules[$unit]['fix'];
    }

    public function next(\DateTime $date)
    {
        $date = clone $date;
        $fixedUnits = array_keys($this->rules);

        $resetUnit = function ($u) use (&$date) {
            $full = array_search($u, Unit::$full);
            $date->modify('-' . (Unit::get($date, $u, Scope::getDefault($u)) - Unit::$defaults[$u]) . ' ' . $full);
        };

        foreach (Unit::$defaults as $unit => $default) {
            if (in_array($unit, $fixedUnits)) {
                $rule = $this->rules[$unit];

                $datePart = Unit::get($date, $unit, $rule['scope']);
                $full = array_search($unit, Unit::$full);

                if ($datePart < $rule['fix']) {
                    $date->modify('+' . ($rule['fix'] - $datePart) . ' ' . $full);

                    // reset everything below current unit
                    array_walk(Unit::lower($unit), $resetUnit);
                } else if ($datePart > $rule['fix']) {
                    // add one to closest non fixed parent
                    $parent = array_pop(array_diff(Unit::higher($unit), $fixedUnits));
                    $date->modify('+1 ' . array_search($parent, Unit::$full));

                    // reset everything below that parent (except for fixed values above the current unit)
                    $reset = array_merge(array_diff(Unit::between($parent, $unit), $fixedUnits), array($unit), Unit::lower($unit));
                    array_walk($reset, $resetUnit);

                    $date->modify('+' . $rule['fix'] - $default . ' ' . $full);
                }
            }
        }

        return $date;
    }
}
