<?php
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Frequency/Scope.php';

use Frequency\Exception;
use Frequency\Scope;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    public function testGetDefault(): void
    {
        $this->assertEquals('M', Scope::getDefault('D'));
    }

    public function testFilter(): void
    {
        $this->assertEquals('M', Scope::filter('D'));
        $this->assertEquals('W', Scope::filter('D', 'W'));
        $this->assertEquals('W', Scope::filter('D', 'week'));
        $this->assertEquals('E', Scope::filter('W', 'epoch'));
        $this->assertEquals('E', Scope::filter('M', 'E'));
        $this->assertEquals('E', Scope::filter('D', 'E'));
    }

    public function testFilterInvalid(): void
    {
        $invalid = array(
            array('X', null), // unknown unit
            array('h', 'X'), // unknown scope
            array('D', 'wek'),
            array('h', 'W'), // known scope, not valid for this unit
            array('W', 'M'),
        );

        foreach ($invalid as list($unit, $scope)) {
            try {
                Scope::filter($unit, $scope);
                $this->fail(sprintf('No exception for %s/%s', $unit, $scope));
            } catch (Exception $e) {
                $this->assertInstanceOf(Exception::class, $e);
            }
        }
    }
}
