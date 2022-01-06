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
        $dateTime = [
            'o:started' => [
                '@value' => $this->getDateTime($this->started()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ],
            'o:ended' => null,
        ];
        if ($this->ended()) {
            $dateTime['o:ended'] = [
               '@value' => $this->getDateTime($this->ended()),
               '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }

        return array_merge(
            [
                'o:status' => $this->status(),
                'o:job_class' => $this->jobClass(),
                'o:args' => $this->args(),
                'o:owner' => $owner,
                'o:step' => $this->step(),
                'o:total_steps' => $this->totalSteps(),
            ],
            $dateTime
        );
    }

    public function status()
    {
        return $this->resource->getStatus();
    }

    public function statusLabel()
    {
        $status = $this->resource->getStatus();
        return isset($this->statusLabels[$status])
            ? $this->statusLabels[$status] : 'Unknown';
    }

    public function jobClass()
    {
        return $this->resource->getClass();
    }

    public function step()
    {
        return $this->resource->getStep();
    }

    public function totalSteps()
    {
        return $this->resource->getTotalSteps();
    }

    /**
     * @return float A number between 0 and 1.
     */
    public function progress()
    {
        return ($total = $this->resource->getTotalSteps())
            ? $this->resource->getStep() / $total
            : 0;
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
