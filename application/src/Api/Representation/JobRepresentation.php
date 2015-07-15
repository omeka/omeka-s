<?php
namespace Omeka\Api\Representation;

class JobRepresentation extends AbstractEntityRepresentation
{
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
    public function getJsonLd()
    {
        $dateTime = array(
            'o:started' => array(
                '@value' => $this->getDateTime($this->started()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ),
            'o:ended' => null,
        );
        if ($this->ended()) {
            $dateTime['o:ended'] = array(
               '@value' => $this->getDateTime($this->ended()),
               '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            );
        }

        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->reference();
        }

        return array_merge(
            array(
                'o:status' => $this->status(),
                'o:job_class' => $this->jobClass(),
                'o:args' => $this->args(),
                'o:owner' => $owner,
            ),
            $dateTime
        );
    }
    
    public function status()
    {
        return $this->getData()->getStatus();
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
