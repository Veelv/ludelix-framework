<?php

namespace Ludelix\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Ludelix\Core\Http\Resources\JsonResource;
use Ludelix\Routing\Resolvers\RouteResolver;
use Ludelix\PRT\Response;
use Ludelix\Core\Container;
use Ludelix\Interface\Logging\LoggerInterface;

// 1. Mock Data / Model
class ResourceUser
{
    public $id = 1;
    public $name = 'John Doe';
    public $email = 'john@example.com';
    public $password = 'secret123'; // Sensitive!
    public $created_at = '2026-01-27 13:00:00';
}

// 2. Resource Implementation
class ResourceVerification extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource->id,
            'name' => strtoupper($this->resource->name),
            'email' => $this->resource->email,
            'member_since' => $this->resource->created_at,
            // Password ignored!
        ];
    }
}

// 3. Setup Components
$container = new Container();
$logger = new class implements LoggerInterface {
    public function debug(string $m, array $c = []): void
    {
    }
    public function info(string $m, array $c = []): void
    {
    }
    public function notice(string $m, array $c = []): void
    {
    }
    public function warning(string $m, array $c = []): void
    {
    }
    public function error(string $m, array $c = []): void
    {
    }
    public function critical(string $m, array $c = []): void
    {
    }
    public function alert(string $m, array $c = []): void
    {
    }
    public function emergency(string $m, array $c = []): void
    {
    }
    public function log(string $l, string $m, array $c = []): void
    {
    }
};

$resolver = new class ($container, $logger) extends RouteResolver {
    public function testNormalize($result)
    {
        return $this->normalizeResponse($result);
    }
};

// 4. Run Tests
echo "--- Ludelix API Resource Verification ---\n\n";

// Test 1: Single Resource Transformation
$user = new ResourceUser();
$resource = new ResourceVerification($user);
$transformed = $resource->toArray();

echo "Test 1: Single Resource transformation\n";
if (isset($transformed['id']) && $transformed['name'] === 'JOHN DOE' && !isset($transformed['password'])) {
    echo "[OK] Resource transformed correctly and protected sensitive fields.\n";
} else {
    echo "[FAIL] Resource transformation failed.\n";
    print_r($transformed);
}

// Test 2: Collection Transformation
$users = [new ResourceUser(), new ResourceUser()];
$collection = ResourceVerification::collection($users);

echo "\nTest 2: Collection transformation\n";
if (count($collection) === 2 && $collection[0]['name'] === 'JOHN DOE') {
    echo "[OK] Collection mapped correctly.\n";
} else {
    echo "[FAIL] Collection mapping failed.\n";
}

// Test 3: RouteResolver Integration (Auto-detection)
$response = $resolver->testNormalize($resource);

echo "\nTest 3: RouteResolver Auto-normalization\n";
if ($response instanceof Response && str_contains($response->getHeader('Content-Type'), 'application/json')) {
    $body = json_decode($response->getContent(), true);
    if ($body['name'] === 'JOHN DOE') {
        echo "[OK] RouteResolver correctly identified and transformed the resource.\n";
    } else {
        echo "[FAIL] RouteResolver transformation content mismatch.\n";
    }
} else {
    echo "[FAIL] RouteResolver did not produce a valid JSON response for the resource.\n";
}

echo "\n--- Verification Complete ---\n";