<?php
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/../src/Frequency/Scope.php';

use Frequency\Scope;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    public function testGetDefault()
    {
        $this->assertEquals('M', Scope::getDefault('D'));
    }

    public function testFilter()
    {
        $this->expectException(Exception::class);
        Scope::filter('X');

        $this->assertEquals('M', Scope::filter('D'));
        $this->assertEquals('D', Scope::filter('h', 'X'));

        $this->assertEquals('W', Scope::filter('D', 'W'));
    }
}
