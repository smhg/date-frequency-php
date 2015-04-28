<?php
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/../src/Frequency/Unit.php';

use Frequency\Unit;

class UnitTest extends \PHPUnit_Framework_TestCase
{
    public function testCompare()
    {
        $this->assertEquals(1, Unit::compare('D', 'M'));

        $this->assertEquals(0, Unit::compare('D', 'D'));

        $this->assertEquals(-1, Unit::compare('W', 'D'));
    }
}
