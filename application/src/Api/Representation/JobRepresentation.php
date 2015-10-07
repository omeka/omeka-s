<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\Job;

class JobRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array
     */
    protected $statusLabels = [
        Job::STATUS_STARTING    => 'Starting',
        Job::STATUS_STOPPING    => 'Stopping',
        Job::STATUS_IN_PROGRESS => 'In Progress',
        Job::STATUS_COMPLETED   => 'Completed',
        Job::STATUS_STOPPED     => 'Stopped',
        Job::STATUS_ERROR       => 'Error',
    ];

    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'job';
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:Job';
    }

    /**
     * {@inheritDoc}
     */
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
            ],
            $dateTime
        );
    }
    
    public function status()
    {
        return $this->getData()->getStatus();
    }

    public function statusLabel()
    {
        $status = $this->getData()->getStatus();
        return isset($this->statusLabels[$status])
            ? $this->statusLabels[$status] : 'Unknown';
    }

    public function jobClass()
    {
        return $this->getData()->getClass();
    }

    public function started()
    {
        return $this->getData()->getStarted();
    }

    public function ended()
    {
        return $this->getData()->getEnded();
    }

    public function args()
    {
        return $this->getData()->getArgs();
    }

    public function log()
    {
        return $this->getData()->getLog();
    }

    /**
     * Get the owner representation of this job.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation(null, $this->getData()->getOwner());
    }
}
