<?php

namespace Ludelix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ludelix\Core\Framework;

class FrameworkTest extends TestCase
{
    public function testFrameworkInstantiation(): void
    {
        $framework = new Framework(__DIR__ . '/../../');
        
        $this->assertInstanceOf(Framework::class, $framework);
        $this->assertEquals('1.0.0', $framework->version());
        $this->assertNotNull($framework->container());
    }

    public function testSingletonPattern(): void
    {
        $framework1 = new Framework(__DIR__ . '/../../');
        $framework2 = Framework::getInstance();
        
        $this->assertSame($framework1, $framework2);
    }

    public function testEnvironmentMethods(): void
    {
        $framework = new Framework(__DIR__ . '/../../');
        
        $this->assertIsString($framework->environment());
        $this->assertIsBool($framework->isProduction());
        $this->assertIsBool($framework->isDebug());
    }
}