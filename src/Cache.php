<?php

namespace Laravel\Installer;

class Cache
{
    /**
     * Get path to cache directory
     *
     * @param string $path
     * @return string
     */
    public static function path(string $path = '')
    {
        return (defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . DIRECTORY_SEPARATOR . 'cache') . ($path ? DIRECTORY_SEPARATOR . $path : null);
    }

    /**
     * List cache directory contents
     *
     * @return array
     */
    public static function list()
    {
        return array_filter(scandir(Cache::path()), function ($name) {
            return in_array($name, ['.', '..']) == false;
        });
    }

    /**
     * Delete file from cache
     *
     * @param string $name
     * @return void
     */
    public static function delete(string $name)
    {
        unlink(Cache::path($name));
    }

    /**
     * Clear cache contents
     *
     * @return void
     */
    public static function clear()
    {
        foreach (Cache::list() as $name) {
            Cache::delete($name);
        }
    }
}