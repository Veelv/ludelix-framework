<?php

namespace Ludelix\Tests\Integration\Ludou;

use Ludelix\Ludou\Core\TemplateEngine;
use PHPUnit\Framework\TestCase;

class LudouCompilerIntegrationTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ludou_integration_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/templates');
        mkdir($this->tempDir . '/cache');

        // Mocking framework functions if needed, but integration should be as real as possible
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /** @test */
    public function it_compiles_sharp_syntax_using_template_engine()
    {
        // 1. Setup Template
        $templateName = 'syntax';
        $path = $this->tempDir . '/templates/' . $templateName . '.ludou';
        $content = <<<'EOT'
        <div #[class="my-class"]>
            #if($show)
                #[$name | upper]
            #endif
        </div>
        EOT;
        file_put_contents($path, $content);

        // 2. Setup Engine
        // Override cache path logic by partial mocking or just relying on internal FileCache logic?
        // TemplateEngine uses FileCache which defaults to 'storage/cache/ludou'. 
        // We need to configure it to use our temp cache.
        // FileCache constructor takes ($enabled, $path). But TemplateEngine manages FileCache internally.

        // Since we can't easily configure cache path in standard TemplateEngine without config,
        // we will subclass it for testing or rely on it verifying content.
        // Actually, let's trust the engine's compilation result.

        // To test compilation details, we need to inspect the compiled output. 
        // But FileCache paths are hashed. How do we find it?
        // We can inspect the cache directory content.

        // For this test, we might need to mock the Cache to spy on put(), OR just rely on render() output correctness.
        // Let's rely on render() output correctness first, as that proves compilation worked.

        $engine = new TemplateEngine([$this->tempDir . '/templates'], false); // Disable cache for this syntax test

        $data = ['show' => true, 'name' => 'ludelix'];
        $output = $engine->render($templateName, $data);

        $this->assertStringContainsString('<div class="my-class">', $output);
        $this->assertStringContainsString('LUDELIX', $output);
        $this->assertStringNotContainsString('#[', $output, 'Sharp syntax should be compiled away');
    }

    /** @test */
    public function it_uses_smart_caching_mechanism()
    {
        // For this, we need to enable cache.
        // But TemplateEngine uses internal path resolution for cache dir often based on framework config.
        // We need to ensure we can check the file modification times.

        // Let's use reflection to inject a custom path or spy on the cache property.

        $templateName = 'cache_test';
        $path = $this->tempDir . '/templates/' . $templateName . '.ludou';
        file_put_contents($path, 'Start #[$value]');

        // We need to mock Config or ensure cache stores in a known location.
        // But let's try to assume relative storage/cache/ludou or similar if no config.

        // BETTER APPROACH:
        // Use the Cache class directly to understand where it stores, or mocking.
        // Let's use a "TestableTemplateEngine" that exposes the cache object.

        $engine = new TestableTemplateEngine([$this->tempDir . '/templates'], true);

        // 1. First Render (Compile & Cache)
        $start = microtime(true);
        $output1 = $engine->render($templateName, ['value' => 1]);
        $this->assertStringContainsString('Start 1', $output1);

        // Find the cache file (Testable engine allows this)
        $cacheFile = $engine->getLastCachePath($templateName);
        $this->assertFileExists($cacheFile);
        $mtime1 = filemtime($cacheFile);

        // Wait 1s
        sleep(1);

        // 2. Second Render (Should use cache)
        $output2 = $engine->render($templateName, ['value' => 1]);
        $mtime2 = filemtime($cacheFile);

        $this->assertEquals($mtime1, $mtime2, "Cache file should not be touched on second render");

        // 3. Touch Template
        sleep(1);
        touch($path);
        clearstatcache();

        // 4. Third Render (Should Recompile -> New Cache File)
        $output3 = $engine->render($templateName, ['value' => 1]);

        // As TemplateEngine uses filemtime in cache key, a new file should be created
        $cacheFile2 = $engine->getLastCachePath($templateName);

        $this->assertNotEquals($cacheFile, $cacheFile2, "A new cache file should be generated when template is modified");
        $this->assertFileExists($cacheFile2, "New cache file must exist");

        // verify content of output3 is still correct (just to be sure)
        $this->assertStringContainsString('Start 1', $output3);
    }

    protected function removeDirectory($dir)
    {
        if (!is_dir($dir))
            return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}

// Helper class to expose internals for testing
class TestableTemplateEngine extends TemplateEngine
{
    public function getLastCachePath($templateName)
    {
        // Mimic the logic in render() to find the key
        $path = $this->findTemplate($templateName);
        $key = md5($path . filemtime($path));

        // We need to know WHERE FileCache stores files.
        // FileCache usually has a base path.
        // Let's use reflection to get the cache path from the cache property.

        $reflection = new \ReflectionClass($this->cache);
        $prop = $reflection->getProperty('cachePath');
        $prop->setAccessible(true);
        $cachePath = $prop->getValue($this->cache);

        return $cachePath . '/' . $key . '.php';
    }
}
