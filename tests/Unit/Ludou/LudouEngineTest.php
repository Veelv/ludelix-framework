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
        
        $this->assertStringContainsString('echo htmlspecialchars((isset($name)', $compiled);
        $this->assertStringContainsString('ENT_QUOTES', $compiled);
    }

    public function testSharpExpressionWithFilter(): void
    {
        $template = 'Hello #[$name|upper]!';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString("LudouFilters::apply('upper'", $compiled);
        $this->assertStringContainsString('htmlspecialchars', $compiled);
    }

    public function testFunctionCall(): void
    {
        $template = '#[t(\'welcome\')]';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString("LudouFunctions::apply('t'", $compiled);
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

    public function testForeachCompilation(): void
    {
        $template = '#foreach($items as $item) Item: #[$item] #endforeach';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString('isset($items)', $compiled);
        $this->assertStringContainsString('is_array($items)', $compiled);
        $this->assertStringContainsString('foreach ($items as $item):', $compiled);
        $this->assertStringContainsString('endforeach; endif;', $compiled);
    }

    public function testForeachKeyValueCompilation(): void
    {
        $template = '#foreach($users as $key => $user) Key: #[$key], User: #[$user] #endforeach';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString('isset($users)', $compiled);
        $this->assertStringContainsString('foreach ($users as $key => $user):', $compiled);
        $this->assertStringContainsString('endforeach; endif;', $compiled);
    }

    public function testForeachSimpleArrayCompilation(): void
    {
        $template = '#foreach($items) Item: #[$item] #endforeach';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString('isset($items)', $compiled);
        $this->assertStringContainsString('foreach ($items as $item):', $compiled);
        $this->assertStringContainsString('endforeach; endif;', $compiled);
    }

    public function testForeachInvalidSyntax(): void
    {
        $template = '#foreach(items as item) Item: #[$item] #endforeach';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString('Erro foreach', $compiled);
        $this->assertStringContainsString('Variável de array inválida', $compiled);
    }

    public function testForeachInvalidItemVariable(): void
    {
        $template = '#foreach($items as item) Item: #[$item] #endforeach';
        $compiled = $this->compiler->compile($template);
        
        $this->assertStringContainsString('Erro foreach', $compiled);
        $this->assertStringContainsString('Variável de item inválida', $compiled);
    }
}