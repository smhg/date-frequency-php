<?php
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/../src/Frequency/Frequency.php';
require_once __DIR__ . '/../src/Frequency/Unit.php';
require_once __DIR__ . '/../src/Frequency/Scope.php';
require_once __DIR__ . '/../src/Frequency/Exception.php';

use Frequency\Frequency;
use Frequency\Exception;
use PHPUnit\Framework\TestCase;

Frequency::$fn['even'] = function($number) {
    return ($number % 2) === 0;
};

Frequency::$fn['odd'] = function($number) {
    return ($number % 2) === 1 || ($number % 2) === -1;
};

Frequency::$fn['leap'] = function($year) {
    return $year % 4 === 0; // just a demo
};

Frequency::$fn['inThirdFullWeek'] = function($week, $date) {
    $firstDay = clone $date;
    $firstDay->modify('first day of this month')
        ->setTime(0, 0, 0);

    $start = clone $firstDay;
    $start->modify('+' . ((8 - (int)$firstDay->format('N')) % 7) + 14 . ' days');

    $end = clone $start;
    $end->modify('+7 days');

    return $start <= $date && $date < $end;
};

class FrequencyTest extends TestCase
{
    public function testValidString()
    {
        $frequency = new Frequency('FT9H');
        $this->assertEquals(9, $frequency->getValue('h'));

        $frequency = new Frequency('F6D/W');
        $this->assertEquals(6, $frequency->getValue('D', 'W'));

        $frequency = new Frequency('F3D/WT10H0M0S');
        $this->assertEquals(3, $frequency->getValue('D', 'week'));
        $this->assertEquals(10, $frequency->getValue('h'));
        $this->assertEquals(0, $frequency->getValue('m'));
        $this->assertEquals(0, $frequency->getValue('s'));

        $frequency = new Frequency(array('h' => array('D' => 9)));
        $this->assertEquals(9, $frequency->getValue('h'));
    }

    public function testInvalidString()
    {
        $this->expectException(Exception::class);
        $frequency = new Frequency('F9H');
        $frequency = new Frequency('FT6D');
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

        $frequency->on('week', 'odd', 'E');
        $this->assertEquals('odd', $frequency->getValue('week', 'epoch'));

        $frequency = new Frequency('F5D2D/WT10H');
        $this->assertEquals(5, $frequency->getValue('day'));
        $this->assertEquals(5, $frequency->getValue('day', 'month'));
        $this->assertEquals(2, $frequency->getValue('day', 'week'));
    }

    public function testNext()
    {
        $frequency = new Frequency(); // every second
        $this->assertTrue($frequency->next(new \DateTime()) instanceof \DateTime);

        $start = new \DateTime('2013-09-02T00:00:00');
        $this->assertEquals(new \DateTime('2013-09-02T00:00:00'), $frequency->next($start));

        $frequency->on('h', 10); // each day at 10:00:00
        $this->assertEquals(new \DateTime('2013-09-02T10:00:00'), $frequency->next($start));

        $frequency->on('h', 0); // each day at 00:00:00
        $this->assertEquals(new \DateTime('2013-09-02T00:00:00'), $frequency->next($start));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T11:10:20');
        $this->assertEquals(new \DateTime('2013-09-02T11:10:20'), $frequency->next($start));

        $frequency->on('h', 10); // each day at 10:00:00
        $this->assertEquals(new \DateTime('2013-09-03T10:00:00'), $frequency->next($start));

        $frequency->on('h', 0); // each day at 00:00:00
        $this->assertEquals(new \DateTime('2013-09-03T00:00:00'), $frequency->next($start));

        $frequency = new Frequency();
        $start = new \DateTime('2013-07-02T00:00:00');

        $frequency->on('month', 8); // each August 1st at 00:00:00
        $this->assertEquals(new \DateTime('2013-08-01T00:00:00'), $frequency->next($start));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T00:00:00');

        $frequency->on('month', 3); // each March 1st at 00:00:00
        $this->assertEquals(new \DateTime('2014-03-01T00:00:00'), $frequency->next($start));

        $frequency->on('month', 3)
            ->on('minute', 30); // each March 1st every 30 minutes
        $this->assertEquals(new \DateTime('2014-03-01T00:30:00'), $frequency->next($start));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T00:00:00');

        $frequency->on('month', 11); // each November 1st at 00:00:00 (across DST)
        $this->assertEquals(new \DateTime('2013-11-01T00:00:00'), $frequency->next($start));

        $frequency = new Frequency();
        $start = new \DateTime('2013-09-02T11:10:20');

        $frequency->on('hour', 10)
            ->on('day', 3, 'week'); // each Wednesday at 10:00:00
        $this->assertEquals(new \DateTime('2013-09-04T10:00:00'), $frequency->next($start));

        $frequency = new Frequency('F1D/WT15H45M');
        $this->assertEquals(new \DateTime('2014-03-10T15:45:00'), $frequency->next(new \DateTime('2014-03-10T00:00:00')));

        $this->assertEquals(new \DateTime('2014-03-10T15:45:01'), $frequency->next(new \DateTime('2014-03-10T15:45:01')));

        $this->assertEquals(new \DateTime('2014-03-17T15:45:00'), $frequency->next(new \DateTime('2014-03-10T15:46:00')));

        $frequency = new Frequency('F1D/WT15H45M0S');
        $this->assertEquals(new \DateTime('2014-03-10T15:45:00'), $frequency->next(new \DateTime('2014-03-04T00:00:00')));

        $frequency = new Frequency('F5D/M2D/WT12H0M0S');
        $this->assertEquals(new \DateTime('2019-02-05T12:00:00'), $frequency->next(new \DateTime('2018-11-25T00:00:00')));
    }

    public function testFilter()
    {
        $f = new Frequency('F(odd)W1D/WT15H45M0S'); // Mondays of odd weeks at 15:45:00
        $this->assertEquals(new \DateTime('2015-05-04T15:45:00'), $f->next(new \DateTime('2015-04-29T00:00:00')));
        $this->assertEquals(new \DateTime('2014-08-11T15:45:00'), $f->next(new \DateTime('2014-08-11T00:00:00')));

        $f = new Frequency('F(even)WT9H30M0S'); // Every day of even weeks at 9:30:00
        $this->assertEquals(new \DateTime('2015-05-11T09:30:00'), $f->next(new \DateTime('2015-05-04T00:00:00')));

        Frequency::$fn['weekend'] = function($weekday) {
          return $weekday === 6 || $weekday === 7;
        };
        $f = new Frequency('F(weekend)D/WT12H0M0S'); // Weekends at 12:00:00
        $this->assertEquals(new \DateTime('2014-08-23T12:00:00'), $f->next(new \DateTime('2014-08-20T00:00:00')));

        Frequency::$fn['inSummer'] = function($month) {
          return $month === 7 || $month === 8;
        };
        $f = new Frequency('F(inSummer)M1DT0H0M0S'); // First day of "summer" months at midnight
        $date = new \DateTime('2014-01-15T00:00:00');
        $date = $f->next($date);
        $this->assertEquals(new \DateTime('2014-07-01T00:00:00'), $date);
        $date = $f->next($date->modify('+1 day'));
        $this->assertEquals(new \DateTime('2014-08-01T00:00:00'), $date);
        $date = $f->next($date->modify('+1 day'));
        $this->assertEquals(new \DateTime('2015-07-01T00:00:00'), $date);

        $f = new Frequency('F(odd)W5D/WT13H0M0S');
        $date = new \DateTime('2016-02-26T13:30:00');
        $date = $f->next($date);
        $this->assertEquals(new \DateTime('2016-03-04T13:00:00'), $date);

        $f = new Frequency('F(odd)W5D/WT13H0M0S');
        $date = new \DateTime('2016-02-26T14:30:00');
        $date = $f->next($date);
        $this->assertEquals(new \DateTime('2016-03-04T13:00:00'), $date);

        $f = new Frequency('F(even)W5D/WT13H0M0S');
        $date = new \DateTime('2016-03-04T13:30:00');
        $date = $f->next($date);
        $this->assertEquals(new \DateTime('2016-03-11T13:00:00'), $date);

        $f = new Frequency('F(inThirdFullWeek)DT9H30M0S');
        $date = new \DateTime('2018-11-01T12:00:00');
        $date = $f->next($date);
        $this->assertEquals(new \DateTime('2018-11-19T09:30:00'), $date);

        $f = new Frequency('F(odd)D4D/WT30M');
        $this->assertEquals(new \DateTime('2018-11-29T00:30:00'), $f->next(new \DateTime('2018-11-25T00:00:00')));
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

        $dates = $frequency->between(new \DateTime('2014-03-04T00:00:00'), new \DateTime('2014-03-10T00:00:00'));
        $this->assertEquals(0, count($dates));
    }

    public function testToString()
    {
        $frequency = new Frequency('FT15H45M');
        $this->assertEquals('FT15H45M', (string)$frequency);

        $frequency = new Frequency('F1D/WT15H45M');
        $this->assertEquals('F1D/WT15H45M', (string)$frequency);

        $frequency = new Frequency('F1D/WT15H45M0S');
        $this->assertEquals('F1D/WT15H45M0S', (string)$frequency);

        $this->assertEquals('F(leap)Y1D/WT15H45M0S', (string)(new Frequency('F(leap)Y1D/WT15H45M0S')));
        $this->assertEquals('F(odd)W1D/WT15H45M0S', (string)(new Frequency('F(odd)W1D/WT15H45M0S')));

        $frequency = new Frequency('F(inThirdFullWeek)WT9H');
        $this->assertEquals('F(inThirdFullWeek)WT9H', (string)$frequency);

        $frequency = new Frequency();
        $frequency->on('month', 2)
            ->on('hour', 10);
        $this->assertEquals('F2MT10H', (string)$frequency);

        $frequency = new Frequency();
        $frequency->on('day', 2, 'week')
            ->on('hour', 10);
        $this->assertEquals('F2D/WT10H', (string)$frequency);


        $frequency = new Frequency();
        $frequency->on('day', 2, 'week')
            ->on('hour', 10);
        $this->assertEquals('F2D/WT10H', (string)$frequency);


        $frequency = new Frequency();
        $frequency->on('day', 2, 'week') // Tuesdays
            ->on('day', 5) // 5th day of the month
            ->on('hour', 10);
        $this->assertEquals('F5D2D/WT10H', (string)$frequency);

        $frequency = new Frequency(array('s' => array('m' => 0)));
        $frequency->on('minute', 0)
            ->on('hour', 10);
        $this->assertEquals('FT10H0M0S', (string)$frequency);

        $frequency = new Frequency(array(
            'D' => array('M' => 'inThirdFullWeek'),
            'h' => array('D' => 9)
        ));
        $this->assertEquals('F(inThirdFullWeek)DT9H', (string)$frequency);
    }

    public function testClone()
    {
        $frequency = new Frequency('F1DT0H0M0S');
        $frequency2 = clone $frequency;
        $frequency2->on('D', 2);

        $this->assertEquals('F1DT0H0M0S', (string)$frequency);
        $this->assertEquals('F2DT0H0M0S', (string)$frequency2);
    }

    public function testMicroseconds() {
        $frequency = new Frequency('FT30M0S');

        $this->assertTrue($frequency->next()->format('u') === '000000');

        $start = new DateTime('T12:00:00.123456');
        $this->assertTrue($frequency->next($start)->format('u') === '000000');
    }
}
