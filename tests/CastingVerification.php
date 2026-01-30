<?php

namespace Ludelix\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\UnitOfWork;
use Ludelix\Database\Metadata\MetadataFactory;
use Ludelix\Database\Attributes\Entity;
use Ludelix\Database\Attributes\Column;
use Ludelix\Database\Attributes\PrimaryKey;
use PDO;

// 1. Setup Mock Entity with Casts
#[Entity(table: 'cast_tests')]
class CastingVerification
{
    #[PrimaryKey]
    #[Column(name: 'id', type: 'integer')]
    public ?int $id = null;

    #[Column(name: 'options', type: 'text', cast: 'json')]
    public array $options = [];

    #[Column(name: 'is_active', type: 'integer', cast: 'bool')]
    public bool $isActive;
}

// 2. Setup Database (SQLite In-Memory)
$config = [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]
    ]
];

try {
    echo "1. Initializing ORM Components...\n";
    $connectionManager = new ConnectionManager($config);
    $metadataFactory = new MetadataFactory();
    $unitOfWork = new UnitOfWork($connectionManager, $metadataFactory);
    $entityManager = new EntityManager($connectionManager, $metadataFactory, $unitOfWork);

    // Create Table
    echo "2. Creating Test Table...\n";
    $pdo = $connectionManager->getConnection();
    $pdo->exec("CREATE TABLE cast_tests (id INTEGER PRIMARY KEY AUTOINCREMENT, options TEXT, is_active INTEGER)");

    // 3. Test Insert (Serialization)
    echo "3. Testing Serialization (INSERT)...\n";
    $test = new CastingVerification();
    $test->options = ['foo' => 'bar', 'nums' => [1, 2, 3]];
    $test->isActive = true;

    $entityManager->persist($test);
    $entityManager->flush();

    $id = $test->id;
    echo " [INFO] Inserted ID: {$id}\n";

    // Verify Raw Data
    $stmt = $pdo->query("SELECT * FROM cast_tests WHERE id = $id");
    $raw = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($raw['is_active'] == 1 && str_contains($raw['options'], '"foo":"bar"')) {
        echo " [PASS] Raw data serialized correctly.\n";
    } else {
        echo " [FAIL] Serialization failed. Raw: " . json_encode($raw) . "\n";
    }

    // 4. Test Find (Hydration)
    echo "4. Testing Hydration (FIND)...\n";

    // Clear UoW to force fetch from DB
    $unitOfWork->clear();
    // Note: EM doesn't have clear(), but UoW clear clears the tracking. However, generic find might rely on identity map if
// we had one.
// Our 'find' implementation creates a new QueryBuilder every time, so it should fetch fresh if not cached elsewhere.

    $fetched = $entityManager->find(CastingVerification::class, $id);

    if (is_array($fetched->options) && $fetched->options['foo'] === 'bar') {
        echo " [PASS] JSON cast correctly to array.\n";
    } else {
        echo " [FAIL] JSON hydration failed.\n";
    }

    if ($fetched->isActive === true) {
        echo " [PASS] Boolean cast correctly.\n";
    } else {
        echo " [FAIL] Boolean hydration failed.\n";
    }

    echo "\nAll Casting Tests Completed.\n";

} catch (\Throwable $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}