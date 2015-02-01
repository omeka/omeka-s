<?php
namespace Omeka\Job\Strategy;

interface StrategyInterface
{
    /**
     * Send the job to be performed.
     *
     * @param string $class
     * @param mixed $args
     */
    public function send($class, $args = null);
}
