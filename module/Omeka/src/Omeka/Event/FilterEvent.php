<?php
namespace Omeka\Event;

use Zend\EventManager\Event;

/**
 * Filter event.
 */
class FilterEvent extends Event
{
    /**
     * @var mixed The argument to filter.
     */
    protected $arg;

    /**
     * {@inheritDoc}
     *
     * @param mixed $arg The argument to filter
     */
    public function __construct($arg = null, $name = null, $target = null,
        $params = null
    ) {
        if (null !== $arg) {
            $this->setArg($arg);
        }
        parent::__construct($name, $target, $params);
    }

    /**
     * Set the argument to filter.
     *
     * @param mixed $arg
     */
    public function setArg($arg)
    {
        $this->arg = $arg;
    }

    /**
     * Get the argument to filter.
     *
     * @return mixed $arg
     */
    public function getArg()
    {
        return $this->arg;
    }
}
