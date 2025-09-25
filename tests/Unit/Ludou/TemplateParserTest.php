<?php

namespace Ludelix\Tests\Unit\Ludou;

use PHPUnit\Framework\TestCase;
use Ludelix\Ludou\Core\TemplateParser;

class TemplateParserTest extends TestCase
{
    protected TemplateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TemplateParser();
    }

    public function testValidForeachExpressions(): void
    {
        $templates = [
            '#foreach($items as $item) Item: #[$item] #endforeach',
            '#foreach($users as $key => $user) Key: #[$key], User: #[$user] #endforeach',
            '#foreach($items) Item: #[$item] #endforeach',
            '#foreach($data["items"] as $item) Item: #[$item] #endforeach',
        ];

        foreach ($templates as $template) {
            $this->assertTrue($this->parser->validate($template), "Template should be valid: $template");
        }
    }

    public function testInvalidForeachExpressions(): void
    {
        $templates = [
            '#foreach(items as item) Item: #[$item] #endforeach', // Missing $ on array
            '#foreach($items as item) Item: #[$item] #endforeach', // Missing $ on item
            '#foreach($items as key => value) Key: #[$key], Value: #[$value] #endforeach', // Missing $ on key and value
            '#foreach("invalid" as $item) Item: #[$item] #endforeach', // String instead of variable
            '#foreach($items as $key => value) Key: #[$key], Value: #[$value] #endforeach', // Mixed $ usage
        ];

        foreach ($templates as $template) {
            $this->expectException(\Exception::class);
            $this->parser->validate($template);
        }
    }

    public function testExtractForeachDirectives(): void
    {
        $template = '#foreach($items as $item) Item: #[$item] #endforeach #foreach($users as $key => $user) User #endforeach';
        $directives = $this->parser->extractDirectives($template);
        
        $foreachExpressions = array_values(array_filter($directives, fn($d) => $d['type'] === 'foreach_expression'));
        $this->assertCount(2, $foreachExpressions);
        
        $this->assertEquals('$items as $item', $foreachExpressions[0]['expression']);
        $this->assertEquals('$users as $key => $user', $foreachExpressions[1]['expression']);
    }

    public function testBalancedDirectives(): void
    {
        $validTemplates = [
            '#if($condition) Content #endif',
            '#foreach($items as $item) Item #endforeach',
            '#if($condition) #foreach($items as $item) Item #endforeach #endif',
        ];

        foreach ($validTemplates as $template) {
            $this->assertTrue($this->parser->validate($template), "Template should be valid: $template");
        }
    }

    public function testUnbalancedDirectives(): void
    {
        $invalidTemplates = [
            '#if($condition) Content', // Missing #endif
            '#foreach($items as $item) Item', // Missing #endforeach
            '#if($condition) Content #endforeach', // Mismatched directives
        ];

        foreach ($invalidTemplates as $template) {
            $this->expectException(\Exception::class);
            $this->parser->validate($template);
        }
    }

    public function testComplexForeachValidation(): void
    {
        // Test array with array access
        $template = '#foreach($data["users"] as $user) User: #[$user["name"]] #endforeach';
        $this->assertTrue($this->parser->validate($template));
        
        // Test nested array access
        $template = '#foreach($config["settings"]["items"] as $key => $value) Setting: #[$key] = #[$value] #endforeach';
        $this->assertTrue($this->parser->validate($template));
    }

    public function testForeachWithComplexArrayAccess(): void
    {
        // This should actually be valid according to our current regex
        $template = '#foreach($users[$index] as $user) User: #[$user] #endforeach';
        $this->assertTrue($this->parser->validate($template));
        
        // Test truly invalid syntax
        $template = '#foreach(users[$index] as $user) User: #[$user] #endforeach';
        $this->expectException(\Exception::class);
        $this->parser->validate($template);
    }
}
