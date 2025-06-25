<?php

namespace Ludelix\Tests\Unit\Ludou;

use PHPUnit\Framework\TestCase;
use Ludelix\Ludou\Core\TemplateEngine;
use Ludelix\Ludou\Core\TemplateCompiler;
use Ludelix\Ludou\Core\FilterManager;

class LudouEngineTest extends TestCase
{
    protected TemplateEngine $engine;
    protected TemplateCompiler $compiler;

    protected function setUp(): void
    {
        $this->engine = new TemplateEngine([], false);
        $this->compiler = new TemplateCompiler();
    }

    public function testSharpExpression(): void
    {
        $template = 'Hello #[$name]!';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString('echo $name', $compiled);
    }

    public function testSharpExpressionWithFilter(): void
    {
        $template = 'Hello #[$name|upper]!';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString("\$renderer->filters['upper']", $compiled);
    }

    public function testFunctionCall(): void
    {
        $template = '#[t(\'welcome\')]';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString("\$renderer->functions['t']", $compiled);
    }

    public function testFilterManager(): void
    {
        $filterManager = new FilterManager();
        
        $this->assertTrue($filterManager->has('upper'));
        $this->assertTrue($filterManager->has('json'));
        
        $result = $filterManager->apply('hello', 'upper');
        $this->assertEquals('HELLO', $result);
    }

    public function testLudouExists(): void
    {
        $this->assertFalse($this->engine->exists('nonexistent'));
    }
}