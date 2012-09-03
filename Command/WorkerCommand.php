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
        $pidFile = $this->getContainer()->getParameter('resque_pid');

        if ($input->getOption('daemon')) {
            $this->startDaemon(
                $pidFile,
                $output,
                $input->getArgument('queue'),
                $input->getOption('log'),
                $input->getOption('interval'),
                $input->getOption('forkCount')
            );
        }
        elseif($input->getOption('stop')) {
            $this->stopDaemon($pid, $output);
        }
        else {
            $this->work(
                $input->getArgument('queue'),
                $input->getOption('log'),
                $input->getOption('interval'),
                $input->getOption('forkCount')
            );
        }
    }

    private function startDaemon($pidFile, OutputInterface $output, $queue, $log, $interval, $forkCount)
    {
        if ($this->checkIsRunning($pidFile)) {
            $output->writeln(array(
                '<error></error>',
                '<error>Resque worker seems allready started</error>',
                '<error></error>'
            ));
            die();
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            syslog(1, 'Unable to start Resque worker as a daemon');
            $output->writeln('Unable to start Resque worker as a daemon');
            die();
        } else if ($pid) {
            // Le père
            file_put_contents($pidFile, $pid);
            $output->writeln('Resque worker started as a daemon');
            die();
        }
        // Le fils
        $this->work($queue, $log, $interval, $forkCount);
    }

    private function stopDaemon($pidFile, OutputInterface $output)
    {
        if ($this->checkIsRunning($pidFile)) {
            $pid = file_get_contents($pidFile);
            $p = new \Symfony\Component\Process\Process('kill -9 ' . $pid);
            $p->run();
            if ($p->isSuccessful()) {
                $output->writeln('<info>Presenter stopped</info>');
                return true;
            } else {
                $output->writeln('<error>Unable to stop presenter</error>');
                die();
            }
        }

        $output->writeln('<error>Presenter not running</error>');
        return true;
    }

    private function checkIsRunning($pidFile)
    {
        if (!file_exists($pidFile)) {
            return false;
        }

        $pid = file_get_contents($pidFile);
        $p = new \Symfony\Component\Process\Process('ls /proc/' . $pid);
        $p->run();
        if (!$p->isSuccessful()) {
            // Remove the pidFile
            unlink($pidFile);
            return false;
        }

        return true;
    }

    private function work($queue, $log, $interval, $forkCount) {
        $worker = $this->getContainer()->get('glit_resque.worker_manager');
        $worker->defineQueue($queue);
        $worker->verbose($log);
        $worker->setInterval($interval);
        $worker->forkInstances($forkCount);
        $worker->daemon();
    }
}