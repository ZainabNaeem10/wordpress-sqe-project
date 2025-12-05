<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testBasicAssertion()
    {
        $this->assertTrue(true, 'Basic test should pass');
    }
    
    public function testMath()
    {
        $this->assertEquals(2 + 2, 4, 'Math should work');
    }
}

