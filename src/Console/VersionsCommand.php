<?php

namespace Laravel\Installer\Console;

use Laravel\Installer\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VersionsCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('versions')
            ->setDescription('Show available Laravel versions');
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
        $formatter = $this->getHelper('formatter');
        $output->writeln('<info>Available Laravel Versions</info>');
        $output->write(' - ');
        foreach (Package::$versions as $i => $v) {
            if ($i !== 0) $output->write(', ');
            $output->write("<comment>{$v}</comment>");
        }
        $output->writeln('');
    }
}
