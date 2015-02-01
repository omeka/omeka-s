<?php
namespace Omeka\Job;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractJob implements JobInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var mixed
     */
    protected $args;

    /**
     * Set job arguments.
     *
     * @param mixed $args
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    /**
     * Get job arguments.
     *
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }
}
