<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\Job;

class JobRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array
     */
    protected $statusLabels = [
        Job::STATUS_STARTING => 'Starting', // @translate
        Job::STATUS_STOPPING => 'Stopping', // @translate
        Job::STATUS_IN_PROGRESS => 'In Progress', // @translate
        Job::STATUS_COMPLETED => 'Completed', // @translate
        Job::STATUS_STOPPED => 'Stopped', // @translate
        Job::STATUS_ERROR => 'Error', // @translate
    ];

    public function getControllerName()
    {
        return 'job';
    }

    public function getJsonLdType()
    {
        return 'o:Job';
    }

    public function getJsonLd()
    {
        $owner = $this->owner();

        return [
            'o:status' => $this->status(),
            'o:job_class' => $this->jobClass(),
            'o:args' => $this->args(),
            'o:owner' => $owner ? $owner->getReference()->jsonSerialize() : null,
            'o:started' => $this->getDateTime($this->started())->getJsonLd(),
            'o:ended' => $this->getDateTime($this->ended())->getJsonLd(),
        ];
    }

    public function status()
    {
        return $this->resource->getStatus();
    }

    public function statusLabel()
    {
        $status = $this->resource->getStatus();
        return $this->statusLabels[$status] ?? 'Unknown';
    }

    public function jobClass()
    {
        return $this->resource->getClass();
    }

    public function started()
    {
        return $this->resource->getStarted();
    }

    public function ended()
    {
        return $this->resource->getEnded();
    }

    public function args()
    {
        return $this->resource->getArgs();
    }

    public function log()
    {
        return $this->resource->getLog();
    }

    /**
     * Get the owner representation of this job.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }
}
