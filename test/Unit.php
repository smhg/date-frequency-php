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

        $tz = new DateTimeZone('America/New_York');
        $this->assertEquals(1, Unit::get(new DateTime('1970-01-05T00:00:00'), 'W', 'E'));
        $this->assertEquals(0, Unit::get(new DateTime('1970-01-01T00:00:00'), 'W', 'E'));
        $this->assertEquals(-1, Unit::get(new DateTime('1969-12-28T23:59:59'), 'W', 'E'));
        $this->assertEquals(2550, Unit::get(new DateTime('2018-11-14T00:00:00'), 'W', 'E'));

        $tz = new DateTimeZone('Europe/Brussels');
        $this->assertEquals(1, Unit::get(new DateTime('1970-01-05T00:00:00'), 'W', 'E'));
        $this->assertEquals(0, Unit::get(new DateTime('1970-01-01T00:00:00'), 'W', 'E'));
        $this->assertEquals(-1, Unit::get(new DateTime('1969-12-28T23:59:59'), 'W', 'E'));
        $this->assertEquals(2550, Unit::get(new DateTime('2018-11-14T00:00:00'), 'W', 'E'));
    }
}
