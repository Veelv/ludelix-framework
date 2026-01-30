<?php

namespace Ludelix\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Ludelix\Database\Attributes\AutoUuid;
use Ludelix\Database\Core\EntityProcessor;
use Ludelix\Database\Metadata\EntityMetadata;

echo "\n--- Ludelix UUID Verification ---\n";

// Mock Entity
class UuidTest
{
    #[AutoUuid]
    public ?string $uuid = null;

    public string $name = "Test User";
}

// Emulate simple Metadata
// In a real scenario, use MetadataFactory, but checking Processor logic directly is isolated enough
$metadata = new EntityMetadata(UuidTest::class);

echo "\nTesting EntityProcessor with #[AutoUuid]...\n";

$processor = new EntityProcessor();
$user = new UuidTest();

echo "Before processing: " . ($user->uuid ?? 'NULL') . "\n";

if ($user->uuid !== null) {
    echo "[FAIL] UUID should be null initially.\n";
} else {
    echo "[OK] UUID is initially null.\n";
}

// Process
try {
    $processor->processEntity($user, $metadata);
    echo "Processing...\n";

    echo "After processing: " . ($user->uuid ?? 'NULL') . "\n";

    if (!empty($user->uuid) && strlen($user->uuid) === 36) {
        echo "[OK] UUID generated successfully: {$user->uuid}\n";
    } else {
        echo "[FAIL] UUID generation failed or invalid format.\n";
    }

    // Test idempotency (should not overwrite existing uuid)
    $existingUuid = $user->uuid;
    $processor->processEntity($user, $metadata);
    echo "Processing again (idempotency check)...\n";

    if ($user->uuid === $existingUuid) {
        echo "[OK] UUID was preserved (not overwritten).\n";
    } else {
        echo "[FAIL] UUID changed! Old: {$existingUuid}, New: {$user->uuid}\n";
    }

} catch (\Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}

echo "\n--- UUID Verification Complete ---\n";