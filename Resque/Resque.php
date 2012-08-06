<?php

namespace Glit\ResqueBundle\Resque;

class Resque {

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

    public function getFaileds() {
        $this->configureResque();

        return \Resque\Resque::redis()->lrange('failed', 0, -1);
    }

    public function requeueFailed($i) {
        $this->configureResque();

        \Resque\Failure::requeue($i);
    }

    public function deleteFailed($i) {
        $this->configureResque();

        $val = rand(0x000000, 0xffffff);
        \Resque\Resque::redis()->lset('failed', $i, $val);
        \Resque\Resque::redis()->lrem('failed', 1, $val);
    }

    public function getAllKeys() {
        $this->configureResque();

        return \Resque\Resque::redis()->keys('*');
    }

    public function getKeyInformation($key) {
        $this->configureResque();
        
        $key = substr($key, strlen(\Resque\Redis::getNamespace()));
        $redis = \Resque\Resque::redis();

        // Find type
        $type = $redis->type($key);

        switch ($type) {
            case 'set':
                $data = $redis->smembers($key);
                break;
            case 'list':
                $data = $redis->lrange($key, 0, -1);
                break;
            case 'string':
                $data = $redis->get($key);
                break;
            default:
                $data = null;
                break;
        }
        
        return array($type, $data);
    }
    
    public function getStats() {
        $this->configureResque();
        
        return array(
            'environment' => '',
            'failed'      => \Resque\Stat::get('failed'),
            'pending'     => 0,
            'processed'   => \Resque\Stat::get('processed'),
            'queues'      => count(\Resque\Resque::queues()),
            'servers'     => '',
            'workers'     => count(\Resque\Worker::all()),
            'working'     => 0
        );
    }

    protected function configureResque() {
        // Set redis backend
        \Resque\Resque::setBackend($this->backend);
        \Resque\Resque::redis()->prefix($this->prefix . ':resque:');
    }

}