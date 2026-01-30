<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Events;

class UploadCompleted
{
    public function __construct(
        public string $path,
        public array $metadata = []
    ) {
    }
}
