<?php
namespace Omeka\Job\Strategy;

use Omeka\Model\Entity\Job;

class PhpCliStrategy extends AbstractStrategy
{
    public function send(Job $job)
    {
        //~ $this->fork($job);
    }
}
