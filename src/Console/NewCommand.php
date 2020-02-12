<?php

namespace Laravel\Installer\Console;

use GuzzleHttp\Exception\ConnectException;
use Laravel\Installer\Cache;
use Laravel\Installer\Package;
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
            ->addArgument('version', InputArgument::OPTIONAL, 'Install specified Laravel version')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('auth', null, InputOption::VALUE_NONE, 'Installs the Laravel authentication scaffolding')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
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

        $output->writeln('<info>Crafting Laravel Application..</info>');
        $output->writeln("  - Using version <comment>{$version}</comment>", OutputInterface::VERBOSITY_VERBOSE);
        $zipFile = Cache::path("laravel-{$version}.zip");
        
        try {
            $output->writeln("  - Downloading package...", OutputInterface::VERBOSITY_VERBOSE);
            $this->download($zipFile, $version);
        } catch (ConnectException $err) {
            $output->writeln("<error>{$err->getMessage()}</error>");
            if (file_exists($zipFile)) {
                $output->writeln("<comment>Attempting to use cached package...</comment>");
            }
        }
        
        $output->writeln("  - Extracting package...", OutputInterface::VERBOSITY_VERBOSE);
        $this->extract($zipFile, $directory);

        $output->writeln("  - Initializing application...", OutputInterface::VERBOSITY_VERBOSE);

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

        $process = Process::fromShellCommandline(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->isSuccessful()) {
            $output->writeln('<comment>Application ready! Build something amazing.</comment>');
        }

        return 0;
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
        Package::download($zipFile, $version);
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
        $filesystem = new Filesystem;

        $response = $archive->open($zipFile, ZipArchive::CHECKCONS);

        if ($response === ZipArchive::ER_NOZIP) {
            throw new RuntimeException('The zip file could not download. Verify that you are able to access: http://cabinet.laravel.com/latest.zip');
        }

        $extractDir = pathinfo($zipFile, PATHINFO_FILENAME);
        $archive->extractTo($extractDir);
        $archive->close();

        $contents = array_diff(scandir($extractDir, SCANDIR_SORT_DESCENDING), ['..', '.']);
        $i = array_key_first($contents);
        if (count($contents) === 1 && is_dir("{$extractDir}/{$contents[$i]}") && fnmatch('laravel*', "{$contents[$i]}")) {
                $filesystem->rename("{$extractDir}/{$contents[$i]}", $directory, true);
                $filesystem->remove($extractDir);
        } else {
            $filesystem->rename($extractDir, $directory, true);
        }
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

        if ($input->getOption('auth')) {
            return 'auth';
        }

        $version = $input->getArgument('version');
        if (in_array($version, Package::$versions)) {
            return $version;
        }

        return 'master';
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
