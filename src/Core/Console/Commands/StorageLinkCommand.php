<?php

namespace Ludelix\Core\Console\Commands;

class StorageLinkCommand
{
    public function handle(): int
    {
        $basePath = getcwd();
        $storagePath = $basePath . '/cubby/up';
        $publicPath = $basePath . '/public/';
        
        if (is_link($publicPath)) {
            echo "The [public/ludelix] link already exists.\n";
            return 0;
        }
        
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        if (symlink($storagePath, $publicPath)) {
            echo "The [public/ludelix] link has been connected to [cubby/up].\n";
            return 0;
        }
        
        echo "Failed to create symbolic link.\n";
        return 1;
    }
}