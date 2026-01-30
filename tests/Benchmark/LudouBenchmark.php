<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Ludelix\Ludou\Core\TemplateEngine;

$tempDir = sys_get_temp_dir() . '/ludou_bench_' . uniqid();
mkdir($tempDir);
mkdir($tempDir . '/templates');
mkdir($tempDir . '/cache');

// Create complex template
$tpl = <<<'EOT'
<!DOCTYPE html>
<html>
<head><title>#[$title]</title></head>
<body>
    <h1>#[$heading]</h1>
    <ul>
    #foreach($items as $item)
        <li #[class="item"]>
            #[$item | upper] - #[now('H:i')]
            #if($item == 'special')
                <strong>SPECIAL!</strong>
            #endif
        </li>
    #endforeach
    </ul>
    <footer>#[$footer | default('Copyright 2024')]</footer>
</body>
</html>
EOT;

file_put_contents($tempDir . '/templates/bench.ludou', $tpl);

$engine = new TemplateEngine([$tempDir . '/templates']);
// Force internal cache path injection if possible, or rely on system temp default
// Since we cant easily configure cache path on Engine from outside without config, 
// we rely on it working.

$data = [
    'title' => 'Benchmark',
    'heading' => 'Results',
    'items' => array_fill(0, 1000, 'item'), // 1000 items
    'items' => array_merge(array_fill(0, 500, 'item'), ['special'], array_fill(0, 500, 'item')),
    'footer' => null
];

echo "Starting Benchmark...\n";

// 1. Cold Compile
$start = microtime(true);
$engine->render('bench', $data);
$coldTime = microtime(true) - $start;
echo "Cold Compile & Render (1000 items): " . number_format($coldTime * 1000, 2) . "ms\n";

// 2. Warm Cache (Smart Compilation Test)
$start = microtime(true);
$engine->render('bench', $data);
$warmTime = microtime(true) - $start;
echo "Warm Cache Render: " . number_format($warmTime * 1000, 2) . "ms\n";

// 3. Hot Loop (100 iterations)
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $engine->render('bench', $data);
}
$avgTime = (microtime(true) - $start) / 100;
echo "Avg Hot Render Time: " . number_format($avgTime * 1000, 2) . "ms\n";

// Cleanup
// ... simplified cleanup

echo "Benchmark Complete.\n";
