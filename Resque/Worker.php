<?php
namespace Glit\ResqueBundle\Resque;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Worker implements ContainerAwareInterface {

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    private $queue = '*';
    private $logging = 'normal';
    private $interval = 5;
    private $fork_count = 1;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer (ContainerInterface $container = null) {
        $this->container = $container;

        $this->logger = $this->container->get('logger');
    }

    public function defineQueue($name) {
        $this->queue = $name;
    }

    public function verbose($mode) {
        $this->logging = $mode;
    }

    public function setInterval($interval) {
        $this->interval = (int)$interval;
    }

    public function forkInstances($count) {
        settype($count, 'int');

        if ($count > 1) {
            if (!function_exists('pntl_fork')) {
               throw new \Exception('pcntl_fork unavailable to use more than one fork count.');
            }
        }

        $this->fork_count = $count;
    }

    private function loglevel() {
        switch($this->logging) {
            case 'verbose' :
                return \Resque_Worker::LOG_VERBOSE;
            case 'normal' :
                return \Resque_Worker::LOG_NORMAL;
            default:
                return \Resque_Worker::LOG_NONE;
        }
    }

    public function daemon() {
        // Register the job instance loader
        \Resque_Event::listen('createInstance', array($this, 'createJobInstance'));

        // Set redis backend
        // TODO : use configuration
        \Resque::setBackend('127.0.0.1:6379');

        if(strpos($this->queue, ':') !== false) {
            list($namespace, $queue) = explode(':', $this->queue);
            \Resque_Redis::prefix($namespace);
            $this->queue = $queue;
        }

        if($this->fork_count > 1 ) {
            for($i = 0; $i < $this->fork_count; $i++) {
                $pid = pcntl_fork();

                if($pid == -1) {
                    throw new \RuntimeException(sprintf('Could not fork worker %s', $i));
                }

                $this->work();
                break;
            }
        }
        else {
            $this->work();
        }
    }

    public function work() {
        $worker = new \Resque_Worker(explode(',', $this->queue));
        $worker->logLevel = $this->loglevel();
        $worker->work($this->interval);

        $this->logger->info(sprintf('Starting worker %s', $worker));
    }

    public function createJobInstance(\Resque_Event_CreateInstance $event) {
        $serviceId = $event->getJob()->payload['class'];

        if($this->container->has($serviceId)) {
            $event->setInstance($this->container->get($serviceId));
        }
    }

}