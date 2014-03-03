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

        $frequency = new Frequency(array('h' => array('fix' => 9)));
        $this->assertEquals(9, $frequency->getValue('h'));
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

    public function testNext()
    {
        $frequency = new Frequency(); // every second
        $this->assertTrue($frequency->next(new \DateTime()) instanceof \DateTime);

        $start = new \DateTime('2013-09-02T00:00:00');
        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-02T00:00:00'));

        $frequency->on('h', 10); // each day at 10:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-02T10:00:00'));

        $frequency->on('h', 0); // each day at 00:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-02T00:00:00'));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T11:10:20');

        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-02T11:10:20'));

        $frequency->on('h', 10); // each day at 10:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-03T10:00:00'));

        $frequency->on('h', 0); // each day at 00:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-03T00:00:00'));

        $frequency = new Frequency();
        $start = new \DateTime('2013-07-02T00:00:00');

        $frequency->on('month', 8); // each August 1st at 00:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2013-08-01T00:00:00'));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T00:00:00');

        $frequency->on('month', 3); // each March 1st at 00:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2014-03-01T00:00:00'));

        $frequency->on('month', 3)
            ->on('minute', 30); // each March 1st every 30 minutes
        $this->assertEquals($frequency->next($start), new \DateTime('2014-03-01T00:30:00'));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T00:00:00');

        $frequency->on('month', 11); // each November 1st at 00:00:00 (across DST)
        $this->assertEquals($frequency->next($start), new \DateTime('2013-11-01T00:00:00'));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T11:10:20');

        $frequency->on('hour', 10)
            ->on('day', 3, 'week'); // each Wednesday at 10:00:00
        $this->assertEquals($frequency->next($start), new \DateTime('2013-09-04T10:00:00'));

        $frequency = new Frequency('F1D/WT15H45M');
        $this->assertEquals(new \DateTime('2014-03-10T15:45:00'), $frequency->next(new \DateTime('2014-03-10T00:00:00')));

        $this->assertEquals(new \DateTime('2014-03-10T15:45:01'), $frequency->next(new \DateTime('2014-03-10T15:45:01')));

        $this->assertEquals(new \DateTime('2014-03-17T15:45:00'), $frequency->next(new \DateTime('2014-03-10T15:46:00')));
    }

    public function testBetween()
    {
        $frequency = new Frequency();
        $frequency->on('hour', 10)
            ->on('minute', 0)
            ->on('second', 0); // each day at 10:00:00
        $dates = $frequency->between(new \DateTime('2013-09-02T00:00:00'), new \DateTime('2013-09-09T00:00:00'));

        $this->assertEquals(7, count($dates));
        $this->assertEquals(new \DateTime('2013-09-02T10:00:00'), $dates[0]);
        $this->assertEquals(new \DateTime('2013-09-08T10:00:00'), $dates[6]);

        $frequency = new Frequency('F1D/WT15H45M0S');
        $dates = $frequency->between(new \DateTime('2014-03-10T00:00:00'), new \DateTime('2014-03-17T00:00:00'));

        $this->assertEquals(1, count($dates));
    }

    public function testToString()
    {
        $frequency = new Frequency('FT15H45M');
        $this->assertEquals('FT15H45M', (string)$frequency);

        $frequency = new Frequency();
        $frequency->on('month', 2)
            ->on('hour', 10);
        $this->assertEquals('F2MT10H', (string)$frequency);

        $frequency = new Frequency();
        $frequency->on('day', 2, 'week')
            ->on('hour', 10);
        $this->assertEquals('F2D/WT10H', (string)$frequency);

        $frequency = new Frequency('F1D/WT15H45M');
        $this->assertEquals('F1D/WT15H45M', (string)$frequency);
    }
}
