<?php
namespace Omeka\Job;

interface JobInterface
{
    /**
     * Perform this job.
     */
    public function perform();

    /**
     * Set job arguments.
     *
     * @param mixed $args
     */
    public function setArgs($args);

    /**
     * Get job arguments.
     *
     * @return mixed
     */
    public function getArgs();
}
