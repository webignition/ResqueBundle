<?php
namespace Glit\ResqueBundle\Resque;

class Queue {
    
    /**
     *
     * @var string
     */
    private $backend;
    
    /**
     *
     * @var string 
     */
    private $prefix;
    
    public function __construct($backend, $prefix) {
        $this->backend = $backend;
        $this->prefix = $prefix;
    }

    public function add($job_name, $queue_name, $args = null) {
        $this->configureResque();
        
        $jobId = \Resque\Resque::enqueue($queue_name, $job_name, $args, true);

        return $jobId;
    }
    
    public function queues() {
        $this->configureResque();
        
        return \Resque\Resque::queues();
    }
    
    protected function configureResque() {
        // Set redis backend
        \Resque\Resque::setBackend($this->backend);
        \Resque\Resque::redis()->prefix($this->prefix.':resque');
    }
}