<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Events;

use Throwable;

class UploadFailed
{
    public function __construct(
        public string $filename,
        public Throwable $exception
    ) {
    }
}
