<?php
namespace Omeka\Api\Representation\Entity;

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
    {}
    
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
