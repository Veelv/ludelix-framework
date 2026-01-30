<?php

namespace Ludelix\Tests\Integration\Ludou;

use Ludelix\Ludou\Core\TemplateEngine;
use PHPUnit\Framework\TestCase;

class LudouErrorTest extends TestCase
{
    protected string $tempDir;
    protected TemplateEngine $engine;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ludou_error_test_' . uniqid();
        mkdir($this->tempDir);

        $this->engine = new TemplateEngine([$this->tempDir], false); // Cache disabled
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function test_it_suggests_similar_template_names()
    {
        // Create a template that exists
        mkdir($this->tempDir . '/users');
        file_put_contents($this->tempDir . '/users/index.ludou', '<h1>Index</h1>');

        try {
            // Try to find a non-existent template that is similar
            $this->engine->render('users.show');
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString("Template 'users.show' não encontrado", $msg);
            $this->assertStringContainsString("Procurado em: " . $this->tempDir, $msg);
            // Since 'users.index' is close to 'users.show' (distance 5? No, 'show' vs 'index'. 4 chars different. Distance 5.)
            // My logic allows distance <= 4.
            // s h o w (4)
            // i n d e x (5)
            // substitutions...
            // Let's make it closer. users.list vs users.lists

        }
    }

    public function test_levenshtein_suggestion()
    {
        mkdir($this->tempDir . '/admin');
        file_put_contents($this->tempDir . '/admin/dashboard.ludou', 'Dash');

        try {
            $this->engine->render('admin.dashbord'); // Typo
            $this->fail('Should throw');
        } catch (\Exception $e) {
            $this->assertStringContainsString("Você quis dizer: 'admin.dashboard'?", $e->getMessage());
        }
    }

    protected function removeDirectory($path)
    {
        if (!is_dir($path))
            return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($path);
    }
}
