<?php

namespace Ludelix\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ludelix\Ludou\Core\TemplateEngine;

class LudouRenderingTest extends TestCase
{
    protected TemplateEngine $engine;
    protected string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ludelix_test_templates';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        $this->engine = new TemplateEngine([$this->tempDir], false);
    }

    protected function tearDown(): void
    {
        $this->cleanupTempDir();
    }

    public function testCompleteTemplateRendering(): void
    {
        $this->createTemplate('test', 'Hello #[$name]!');

        $result = $this->engine->render('test', ['name' => 'world']);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('world', $result);
    }

    public function testTemplateWithFunctions(): void
    {
        $this->createTemplate('greeting', 'Welcome #[t(\'hello\')]');

        $result = $this->engine->render('greeting', ['user' => 'John']);
        $this->assertStringContainsString('Welcome', $result);
    }

    public function testTemplateWithDirectives(): void
    {
        $template = '#if($show) Visible content #endif';
        $this->createTemplate('conditional', $template);

        $result = $this->engine->render('conditional', ['show' => true]);
        $this->assertStringContainsString('Visible content', $result);
    }

    public function testTemplateWithLoop(): void
    {
        $template = '#foreach($items as $item) Item: #[$item] #endforeach';
        $this->createTemplate('loop', $template);

        $result = $this->engine->render('loop', ['items' => ['A', 'B', 'C']]);
        $this->assertStringContainsString('Item: A', $result);
        $this->assertStringContainsString('Item: B', $result);
        $this->assertStringContainsString('Item: C', $result);
    }

    public function testTemplateWithSections(): void
    {
        $template = 'Simple content';
        $this->createTemplate('page', $template);

        $result = $this->engine->render('page');
        $this->assertStringContainsString('Simple content', $result);
    }

    public function testTemplateWithMultipleFilters(): void
    {
        $template = 'Result: #[$text]';
        $this->createTemplate('filters', $template);

        $result = $this->engine->render('filters', ['text' => 'hello world']);
        $this->assertStringContainsString('hello world', $result);
    }

    public function testTemplateNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("não encontrado");

        $this->engine->render('nonexistent');
    }

    public function testTemplateExists(): void
    {
        $this->createTemplate('exists', 'Content');

        $this->assertTrue($this->engine->exists('exists'));
        $this->assertFalse($this->engine->exists('nonexistent'));
    }

    protected function createTemplate(string $name, string $content): void
    {
        file_put_contents($this->tempDir . '/' . $name . '.ludou', $content);
    }

    protected function cleanupTempDir(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }
}