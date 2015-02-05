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

    /**
     * Get an argument by name.
     *
     * Assumes that self::$args is an array.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getArg($name)
    {
        if (!is_array($this->args)) {
            return null;
        }
        if (!array_key_exists($name, $this->args)) {
            return null;
        }
        return $this->args[$name];
    }
}
