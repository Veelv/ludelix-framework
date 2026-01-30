<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Listeners;

use Ludelix\Infrastructure\Events\UploadCompleted;

class CleanupExpiredUploads
{
    public function handle(UploadCompleted $event): void
    {
        // Placeholder for cleanup logic
        // Implementation would check for temporary files and remove them
    }
}
