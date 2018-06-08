<?php
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/../src/Frequency/Unit.php';

use Frequency\Unit;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function testCompare()
    {
        $this->assertEquals(1, Unit::compare('D', 'M'));

        $this->assertEquals(0, Unit::compare('D', 'D'));

        $this->assertEquals(-1, Unit::compare('W', 'D'));
    }

    public function testGet()
    {
        $this->assertEquals(1, Unit::get(new DateTime('2014-02-03T00:00:00'), 'D', 'W'));
        $this->assertEquals(7, Unit::get(new DateTime('2014-02-09T00:00:00'), 'D', 'W'));
        $this->assertEquals(182, Unit::get(new DateTime('2018-07-01T00:00:00'), 'D', 'Y'));
        $this->assertEquals(1, Unit::get(new DateTime('2021-01-01T00:00:00'), 'D', 'Y'));
        $this->assertEquals(26, Unit::get(new DateTime('2018-07-01T00:00:00'), 'W', 'Y'));
        $this->assertEquals(53, Unit::get(new DateTime('2021-01-01T00:00:00'), 'W', 'Y'));
    }
}
