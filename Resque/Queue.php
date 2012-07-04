<?php
namespace Glit\ResqueBundle\Resque;

class Queue {

    public static function add($job_name, $queue_name, $args = null) {
        // Set redis backend
        // TODO : use configuration
        \Resque\Resque::setBackend('127.0.0.1:6379');

        $jobId = \Resque\Resque::enqueue($queue_name, $job_name, $args, true);

        return $jobId;
    }
}