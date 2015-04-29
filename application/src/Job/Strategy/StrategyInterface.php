<?php
namespace Omeka\Job\Strategy;

use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

interface StrategyInterface extends ServiceLocatorAwareInterface 
{
    /**
     * Send the job to be performed.
     *
     * @param string $class
     * @param mixed $args
     */
    public function send(Job $job);
}
