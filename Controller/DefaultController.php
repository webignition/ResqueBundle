<?php

namespace Glit\ResqueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller {

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction () {
        $queues = array();
        foreach (\Resque::queues() as $queue) {
            $queues[$queue] = \Resque::size($queue);
        }

        $workers = array();
        foreach (\Resque_Worker::all() as $worker) {
            /** @var $worker \Resque_Worker */
            list($host, $pid, $_) = explode(':', $worker->__toString());
            $data = $worker->job();
            if(!empty($data)) {
                $workers[] = array(
                    'where' => $host . ':' . $pid,
                    'queue' => $data['queue'],
                    'job'   => $data['payload']['class']
                );
            }
        }

        return array(
            'queues'  => $queues,
            'workers' => $workers
        );
    }

    /**
     * @Route("/failed")
     * @Template()
     */
    public function failedAction() {
        $faileds = \Resque::redis()->lrange('failed', 0, -1);

        $data = array();
        foreach($faileds as $index => $fail) {
            $data[$index] = (json_decode($fail));

        }
        return array('data' => $data);
    }

    /**
     * @Route("/failed/{i}/requeue")
     */
    public function requeueFailedAction($i) {
        \Resque_Failure::requeue($i);

        return $this->redirect($this->generateUrl('glit_resque_default_failed'));
    }

    /**
     * @Route("/failed/{i}/delete")
     */
    public function deleteFailedAction($i) {
        $val = rand(0x000000, 0xffffff);
        \Resque::redis()->lset('failed', $i, $val);
        \Resque::redis()->lrem('failed', 1, $val);

        return $this->redirect($this->generateUrl('glit_resque_default_failed'));
    }

    /**
     * @Route("/stats/resque")
     * @Template()
     */
    public function statsResqueAction () {
        return array(
            'environment' => '',
            'failed'      => \Resque_Stat::get('failed'),
            'pending'     => 0,
            'processed'   => \Resque_Stat::get('processed'),
            'queues'      => count(\Resque::queues()),
            'servers'     => '',
            'workers'     => count(\Resque_Worker::all()),
            'working'     => 0
        );
    }

    /**
     * @Route("/stats/redis")
     * @Template()
     */
    public function statsRedisAction () {
        return array();
    }

    /**
     * @Route("/stats/keys")
     * @Template()
     */
    public function statsKeysAction () {
        $ids = explode(' ', \Resque::redis()->keys('*'));

        $keys = array();
        foreach ($ids as $key) {
            $type = \Resque::redis()->type($key);
            switch($type) {
                case 'set':
                    $data = \Resque::redis()->smembers($key);
                    break;
                case 'list':
                    $data = \Resque::redis()->lrange($key, 0, -1);
                    break;
                case 'string':
                    $data = \Resque::redis()->get($key);
                    break;
            }

            $keys[] = array(
                'id' => $key,
                'type' => $type,
                'data' => $data
            );
        }
        return array('keys' => $keys);
    }
}