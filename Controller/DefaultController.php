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
        foreach ($this->get('glit_resque.queue_manager')->queues() as $queue) {
            $queues[$queue] = \Resque\Resque::size($queue);
        }

        $workers = array();
        foreach (\Resque\Worker::all() as $worker) {
            /** @var $worker \Resque\Worker */
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
        $faileds = $this->get('glit_resque.resque_manager')->getFaileds();

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
        $this->get('glit_resque.resque_manager')->requeueFailed($i);

        return $this->redirect($this->generateUrl('glit_resque_default_failed'));
    }

    /**
     * @Route("/failed/{i}/delete")
     */
    public function deleteFailedAction($i) {
        $this->get('glit_resque.resque_manager')->deleteFailed($i);
        
        return $this->redirect($this->generateUrl('glit_resque_default_failed'));
    }

    /**
     * @Route("/stats/resque")
     * @Template()
     */
    public function statsResqueAction () {
        return $this->get('glit_resque.resque_manager')->getStats();
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
        $ids = $this->get('glit_resque.resque_manager')->getAllKeys();

        $keys = array();

        foreach ($ids as $key) {
            list($type, $data) = $ids = $this->get('glit_resque.resque_manager')->getKeyInformation($key);
            
            $keys[] = array(
                'id' => $key,
                'type' => $type,
                'data' => $data
            );
        }
        return array('keys' => $keys);
    }
}