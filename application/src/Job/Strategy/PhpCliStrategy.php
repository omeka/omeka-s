<?php
namespace Omeka\Job\Strategy;

class PhpCliStrategy extends AbstractStrategy
{
    public function send($class, $args = null)
    {
        /*
        $process = new Process;
        $process->setClass($class);
        $process->setArgs($args);

        $entityManager->persist($process);
        $entityManager->flush();

        $this->fork($process);
        */
    }
}
