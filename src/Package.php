<?php

namespace Laravel\Installer;

use Composer\Semver\Semver;
use Exception;
use GuzzleHttp\Client;

class Package
{
    /**
     * Available Laravel package versions
     *
     * @var array
     */
    public static $versions = [
        'master', 'develop', 'auth', '7', '6', '5.8', '5.7', '5.6', '5.5',
    ];

    /**
     * Dowload Laravel package with specified version
     *
     * @param string $fileName
     * @param string $version
     */
    public static function download($fileName, $version = 'master')
    {
        if (!in_array($version, self::$versions)) {
            $version = 'master';
        }

        switch ($version) {
            case 'develop':
                $url = 'http://cabinet.laravel.com/latest-develop.zip';
                break;
            case 'master':
                $url = 'http://cabinet.laravel.com/latest.zip';
                break;
            case 'auth':
                $url = 'http://cabinet.laravel.com/latest-auth.zip';
                break;
            default:
                $releases = self::getReleases();
                foreach ($releases as $release) {
                    if (Semver::satisfies($release, "^{$version}")) {
                        $url = "https://github.com/laravel/laravel/archive/{$release}.zip";
                    }
                }                
        }

        if (empty($url)) {
            throw new Exception("Unable to find release version {$version}");
        }

        $response = (new Client)->get($url);

        if (!file_exists(dirname($fileName))) {
            mkdir(dirname($fileName), 0777, true);
        }

        file_put_contents($fileName, $response->getBody());
        return;
    }

    /**
     * Get releases from GitHub
     * @return array
     */
    public static function getReleases()
    {
        $response = (new Client)->request('GET', 'https://api.github.com/repos/laravel/laravel/releases');
        $releases = array_map(function ($release) {
            return $release->name;
        }, json_decode($response->getBody()));
        return $releases;
    }
}