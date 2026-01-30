<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Events;

class UploadStarted
{
    public function __construct(
        public string $filename,
        public int $size
    ) {
    }
}
