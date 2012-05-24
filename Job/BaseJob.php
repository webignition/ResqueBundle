<?php
namespace Glit\ResqueBundle\Job;

use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BaseJob  {

    public $args;
    public $job;
    public $queue;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return Registry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    public function getDoctrine()
    {
        if (!$this->container->has('doctrine')) {
            throw new \LogicException('The DoctrineBundle is not installed in your application.');
        }

        return $this->get('doctrine');
    }

    /**
     * @return \Glit\GitoliteBundle\Admin\Gitolite
     */
    protected function getGitoliteAdmin () {
        return $this->get('glit_gitolite.admin');
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string  $route      The name of the route
     * @param mixed   $parameters An array of parameters
     * @param Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->container->get('router')->generate($route, $parameters, $absolute);
    }

    protected function get($serviceId) {
        return $this->container->get($serviceId);
    }

}