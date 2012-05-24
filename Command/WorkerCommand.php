<?php
namespace Glit\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;


class WorkerCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('resque:worker')
            ->setDescription("Starts Resque worker to read queues. Use resque:worker --help for + info")
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', '*')
            ->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'Verbose mode [verbose|normal|none]')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Daemon check interval (in seconds)', 5)
            ->addOption('forkCount', 'f', InputOption::VALUE_OPTIONAL, 'Fork count instances', 1)
            ->addOption('daemon', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Run worker as daemon')
            ->addOption('stop', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Run worker as daemon')
            ->setHelp(<<<EOF
Worker will run all jobs enqueue by PHPResqueBundle\Resque\Queue command line and defined by Queue class.
You can run more than one queue per time. In this case input all queues names separated by commas on the 'queue' argument.
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($input->getOption('daemon')) {
            $pid = pcntl_fork();

            if($pid == -1) {
                throw new \RuntimeException(sprintf('Could not fork worker %s', $i));
            }
            elseif($pid) {
                $output->writeln('Worker started as daemon.');
                return;
            }
            else {

            }
        }

        $worker = new \Glit\ResqueBundle\Resque\Worker();
        $worker->setContainer($this->getContainer());
        $worker->defineQueue($input->getArgument('queue'));
        $worker->verbose($input->getOption('log'));
        $worker->setInterval($input->getOption('interval'));
        $worker->forkInstances($input->getOption('forkCount'));
        $worker->daemon();
    }
}