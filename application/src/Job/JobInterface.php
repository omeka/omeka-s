<?php
namespace Omeka\Job;

interface JobInterface
{
    /**
     * Perform this job.
     */
    public function perform();
}
