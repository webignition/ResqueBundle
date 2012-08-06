<?php
namespace Glit\ResqueBundle\Command;

use Glit\ResqueBundle\Resque\Queue;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class QueueCommand extends ContainerAwareCommand {

    protected function configure() {
        parent::configure();
        $this->setName('resque:queue')
            ->setDescription('Attach a class into any specified queue to be performed.')
            ->addArgument('job', InputArgument::REQUIRED, 'Job service id')
            ->addArgument('queue_name', InputArgument::OPTIONAL, 'Queue name', 'default')
            ->addArgument('args', InputArgument::OPTIONAL, 'job arguments as json value', null)
            ->setHelp(<<<EOF
resque:queue Your\\ProjectBundle\\SubprojectBundle\\JobClass mailer
This will put JobClass into 'mailer' queue

You can enqueue new jobs using a PHPResqueBundle\Resque\Queue
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if($input->getArgument('args') != null) {
            $args = json_decode($input->getArgument('args'));
        }

        $job = $this->getContainer()->get('glit_resque.queue_manager')->add($input->getArgument('job'), $input->getArgument('queue_name'), $args);
        $output->writeln("Job captured. Input at {$input->getArgument('queue_name')} queue. Job id {$job}");
    }
}