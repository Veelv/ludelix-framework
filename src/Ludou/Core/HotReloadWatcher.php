<?php

namespace Ludelix\Ludou\Core;

/**
 * Hot Reload Watcher
 * 
 * Watches template files for changes and triggers recompilation
 */
class HotReloadWatcher
{
    protected array $watchedFiles = [];
    protected array $lastModified = [];
    protected bool $enabled;

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
    }

    public function watch(string $templatePath): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->watchedFiles[] = $templatePath;
        $this->lastModified[$templatePath] = filemtime($templatePath);
    }

    public function hasChanges(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        foreach ($this->watchedFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $currentModified = filemtime($file);
            if ($currentModified > ($this->lastModified[$file] ?? 0)) {
                $this->lastModified[$file] = $currentModified;
                return true;
            }
        }

        return false;
    }

    public function getChangedFiles(): array
    {
        $changed = [];

        foreach ($this->watchedFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $currentModified = filemtime($file);
            if ($currentModified > ($this->lastModified[$file] ?? 0)) {
                $changed[] = $file;
                $this->lastModified[$file] = $currentModified;
            }
        }

        return $changed;
    }

    public function clearWatch(): void
    {
        $this->watchedFiles = [];
        $this->lastModified = [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
