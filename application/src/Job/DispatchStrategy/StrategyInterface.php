<?php
namespace Omeka\Job\DispatchStrategy;

use Omeka\Entity\Job;

interface StrategyInterface
{
    /**
     * Send the job to be performed.
     *
     * @param string $class
     * @param mixed $args
     */
    public function send(Job $job);
}
