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
     * {@inheritDoc}
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    /**
     * {@inheritDoc}
     */
    public function getArgs()
    {
        return $this->args;
    }
}
