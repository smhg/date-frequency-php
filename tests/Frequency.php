<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Frequency\Frequency;
use Frequency\Exception;

class FrequencyTest extends \PHPUnit_Framework_TestCase
{
    public function testValidString()
    {
        $frequency = new Frequency('FT9H');
        $this->assertEquals(9, $frequency->getValue('h'));

        $frequency = new Frequency('F6D/W');
        $this->assertEquals(6, $frequency->getValue('D', 'W'));

        $frequency = new Frequency('F3D/WT10H0M0S');
        $this->assertEquals(3, $frequency->getValue('D', 'W'));
        $this->assertEquals(10, $frequency->getValue('h'));
        $this->assertEquals(0, $frequency->getValue('m'));
        $this->assertEquals(0, $frequency->getValue('s'));
    }

    public function testInvalidString()
    {
        try {
            $frequency = new Frequency('F9H');
            $frequency = new Frequency('FT6D');
        } catch (Exception $e) {
            return;
        }

        $this->fail('Invalid string format not detected.');
    }

    public function testOnAndGetValue()
    {
        $frequency = new Frequency();

        $frequency->on('hour', 10);
        $this->assertEquals(10, $frequency->getValue('hour'));

        $frequency->on('day', 18);
        $this->assertEquals(18, $frequency->getValue('D'));

        $frequency->on('D', 3, 'week');
        $this->assertEquals(3, $frequency->getValue('day', 'W'));
    }
}
