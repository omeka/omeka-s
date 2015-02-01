<?php
namespace Omeka\Job\Strategy;

class SynchronousStrategy extends AbstractStrategy
{
    public function send($class, $args = null)
    {
        $job = new $class;
        $job->setServiceLocator($this->getServiceLocator());
        $job->setArgs($args);
        $job->perform();
    }
}
