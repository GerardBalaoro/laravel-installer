<?php

namespace Laravel\Installer\Console;

use FilesystemIterator;
use GuzzleHttp\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ZipArchive;

class NewCommand extends Command
{
    /**
     * Available Laravel versions
     * @var array
     */
    protected $versions = [
        '6.0', '5.8', '5.7', '5.6', '5.5', '5.4'
    ];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Laravel application')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists');

        foreach ($this->versions as $version) {
            $this->addOption($version, null, InputOption::VALUE_NONE, "Installs the latest \"{$version}\" release");
        }
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! extension_loaded('zip')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $name = $input->getArgument('name');
        $version = $this->getVersion($input);

        $directory = $name && $name !== '.' ? getcwd().'/'.$name : getcwd();

        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        $output->writeln('<info>Crafting Laravel ' . (in_array($version, $this->versions) ? "{$version} " : ''). 'Application...</info>');

        $this->download($zipFile = $this->makeFilename(), $version)
             ->extract($zipFile, $directory)
             ->prepareWritableDirectories($directory, $output)
             ->cleanUp($zipFile);

        $composer = $this->findComposer();

        $commands = [
            $composer.' install --no-scripts',
            $composer.' run-script post-root-package-install',
            $composer.' run-script post-create-project-cmd',
            $composer.' run-script post-autoload-dump',
        ];

        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                return $value.' --quiet';
            }, $commands);
        }

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/laravel_'.md5(time().uniqid()).'.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function download($zipFile, $version = 'master')
    {
        switch ($version) {
            case 'develop':
                $url = 'http://cabinet.laravel.com/latest-develop.zip';
                break;
            case 'master':
            default:
                $url = 'http://cabinet.laravel.com/latest.zip';
                break;
        }

        if (in_array($version, $this->versions)) {
            foreach ($this->getReleases() as $release) {
                if (fnmatch("v{$version}.?", $release)) {
                    $url = "https://github.com/laravel/laravel/archive/{$release}.zip";
                }
            }
        }

        $response = (new Client)->get($url);

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $response = $archive->open($zipFile, ZipArchive::CHECKCONS);

        if ($response === ZipArchive::ER_NOZIP) {
            throw new RuntimeException('The zip file could not download. Verify that you are able to access: http://cabinet.laravel.com/latest.zip');
        }

        $extractDir = pathinfo($zipFile, PATHINFO_FILENAME);
        $archive->extractTo($extractDir);
        $archive->close();

        $contents = array_diff(scandir($extractDir, SCANDIR_SORT_DESCENDING), ['..', '.']);
        if (count($contents) === 1) {
            $i = array_key_first($contents);
            if (is_dir("{$extractDir}/{$contents[$i]}") && fnmatch('laravel*', "{$contents[$i]}")) {
                $this->moveDirectoryRecursive("{$extractDir}/{$contents[$i]}", $directory);
                $this->deleteDirectoryRecursive($extractDir);
            }
        }
        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string  $zipFile
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);
        return $this;
    }

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @param  string  $appDirectory
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return $this
     */
    protected function prepareWritableDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR.'bootstrap/cache', 0755, 0000, true);
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR.'storage', 0755, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>You should verify that the "storage" and "bootstrap/cache" directories are writable.</comment>');
        }

        return $this;
    }

    /**
     * Move directory contents to another directory
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    protected function moveDirectoryRecursive($source, $destination)
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
    protected function deleteDirectoryRecursive($directory)
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
     * Get the version that should be downloaded.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev')) {
            return 'develop';
        }

        foreach ($this->versions as $version) {
            if ($input->getOption($version)) {
                return $version;
            }
        }

        return 'master';
    }

    /**
     * Get releases from GitHub
     * @return array
     */
    protected function getReleases()
    {
        $response = (new Client)->request('GET', 'https://api.github.com/repos/laravel/laravel/releases');
        $releases = array_map(function ($release) {
            return $release->name;
        }, json_decode($response->getBody()));
        return $releases;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }
}
