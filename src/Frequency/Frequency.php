<?php
namespace Frequency;

class Frequency
{
    protected $rules = array();

    public function __construct($str = null)
    {
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

                $this->rules[$u] = array(
                    'fix' => $matches[$i][0]
                );

                if ($matches[$i + 1][0]) {
                    $this->rules[$u]['scope'] = $matches[$i + 1][0];
                }
            }
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
        }

        if (func_num_args() === 3) {
            $options['scope'] = Unit::filter(func_get_arg(2));
        }

        if (!$options['fix']) {
            $options['fix'] = Unit::$defaults[$unit];
        }

        $options['scope'] = Scope::filter($unit, isset($options['scope']) ? $options['scope'] : null);

        $this->rules[$unit] = $options;

        return $this;
    }

    public function getValue($unit, $scope = null)
    {
        $unit = Unit::filter($unit);

        if (!$unit) {
            throw new Exception('Invalid unit');
        }

        if (!$this->rules[$unit] || ($scope && (!$this->rules[$unit]['scope'] || $this->rules[$unit]['scope'] !== $scope))) {
            return;
        }

        return $this->rules[$unit]['fix'];
    }
}
