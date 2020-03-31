<?php

namespace Laravel\Installer\Console;

use Laravel\Installer\Cache;
use Laravel\Installer\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('cache')
            ->setDescription('Download Laravel packages to cache')
            ->addArgument('versions', InputArgument::IS_ARRAY, 'Versions to download', ['master'])
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Downloads all releases')
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Cleans cache directory');
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
        $versions = $input->getOption('all') ? Package::$versions : $input->getArgument('versions');

        foreach ($versions as $version) {
            $output->writeln('Downloading <info>Laravel Package</info> (<comment>' . (is_numeric($version) ? "v{$version}" : $version) . '</comment>)...');
            Package::download(Cache::path("laravel-{$version}.zip"), $version);
        }

        $output->writeln(PHP_EOL . '<info>Finished.</info>');
        return 0;
    }

    /**
     * Get the version that should be downloaded.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        foreach (Package::$versions as $version) {
            if ($input->getOption($version)) {
                return $version;
            }
        }

        return 'master';
    }
}
