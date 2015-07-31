<?php
namespace Omeka\Log\Writer;

use Omeka\Entity\Job as JobEntity;
use Zend\Log\Writer\AbstractWriter;
use Zend\Log\Formatter\Simple as SimpleFormatter;

class Job extends AbstractWriter
{
    /**
     * @var JobEntity
     */
    protected $job;

    /**
     * @param JobEntity $job
     */
    public function __construct(JobEntity $job)
    {
        $this->job = $job;
        $this->formatter = new SimpleFormatter;
    }

    /**
     * Log to the Job entity.
     *
     * @param array $event
     */
    protected function doWrite(array $event)
    {
        $this->job->addLog($this->formatter->format($event));
    }
}
