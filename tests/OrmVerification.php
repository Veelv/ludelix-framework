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

// 1. Setup Mock Entity
#[Entity(table: 'users')]
class OrmVerification
{
    #[PrimaryKey]
    #[Column(name: 'id', type: 'integer')]
    public ?int $id = null;

    #[Column(name: 'name', type: 'string')]
    public string $name;

    #[Column(name: 'email', type: 'string')]
    public string $email;
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

    // Create Table manually for testing
    echo "2. Creating Test Table...\n";
    $pdo = $connectionManager->getConnection();
    $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)");

    // 3. Test Insert
    echo "3. Testing INSERT...\n";
    $user = new OrmVerification();
    $user->name = "Estevao User";
    $user->email = "test@example.com";

    $entityManager->persist($user);
    $entityManager->flush();

    if ($user->id) {
        echo " [PASS] User ID assigned: {$user->id}\n";
    } else {
        echo " [FAIL] User ID not assigned.\n";
    }

    // 4. Test Find
    echo "4. Testing FIND...\n";
    $foundUser = $entityManager->find(OrmVerification::class, $user->id);
    if ($foundUser && $foundUser->name === "Estevao User") {
        echo " [PASS] User found correctly.\n";
    } else {
        echo " [FAIL] User not found or data mismatch.\n";
    }

    // 5. Test Update
    echo "5. Testing UPDATE...\n";
    $foundUser->name = "Updated Name";
    // In a real scenario, UoW would track this. Since we have a simple UoW, re-registering managed might be needed or dirty checking.
    // Our refactored UoW iterates 'managedEntities'.
    $entityManager->flush();

    $updatedUser = $entityManager->find(OrmVerification::class, $user->id);
    if ($updatedUser->name === "Updated Name") {
        echo " [PASS] User updated correctly.\n";
    } else {
        echo " [FAIL] Update failed. Name is: {$updatedUser->name}\n";
    }

    // 6. Test Delete
    echo "6. Testing DELETE...\n";
    $entityManager->remove($foundUser);
    $entityManager->flush();

    $deletedUser = $entityManager->find(OrmVerification::class, $user->id);
    if ($deletedUser === null) {
        echo " [PASS] User deleted correctly.\n";
    } else {
        echo " [FAIL] User still exists.\n";
    }

    echo "\nAll Verification Steps Completed.\n";

} catch (\Throwable $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}