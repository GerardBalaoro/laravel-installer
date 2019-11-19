<?php

namespace Laravel\Installer;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Helpers
{
    /**
     * Move directory contents to another directory
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    public static function moveDirectoryRecursive(string $source, string $destination)
    {
        $crawler = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($crawler as $path) {
            if ($path->isFile()) {
                $newPath = "{$destination}/" . ltrim(substr($path, strlen($source)),"\/");
                if (!file_exists(dirname($newPath))) {
                    mkdir(dirname($newPath), 0777, true);
                }
                rename($path->__toString(), $newPath);
            }
        }
    }

    /**
     * Delete a directory and its contents
     *
     * @param string $directory
     * @return void
     */
    public static function deleteDirectoryRecursive(string $directory)
    {
        $crawler = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($crawler as $path) {
            if ($path->isFile()) {
                unlink($path->__toString());
            } else {
                rmdir($path->__toString());
            }
        }

        rmdir($directory);
    }

    /**
     * Get path to cache directory
     *
     * @param string $path
     */
    public static function cachePath(string $path = '')
    {
        return (defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . DIRECTORY_SEPARATOR . 'cache') . ($path ? DIRECTORY_SEPARATOR . $path : null);
    }
}