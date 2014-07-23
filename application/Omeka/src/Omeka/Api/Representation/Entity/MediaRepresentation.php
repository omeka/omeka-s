<?php
namespace Omeka\Api\Representation\Entity;

class MediaRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function jsonSerializeResource()
    {
        return array(
            'type' => $this->getType(),
            'data' => $this->getMediaData(),
            'item' => $this->getReference(
                null, $this->getData()->getItem(), $this->getAdapter('items')
            ),
        );
    }

    /**
     * Get the media type
     *
     * @return string
     */
    public function getType()
    {
        return $this->getData()->getType();
    }

    /**
     * Get the media data.
     *
     * Named getMediaData() so as not to override parent::getData().
     *
     * @return mixed
     */
    public function getMediaData()
    {
        return $this->getData()->getData();
    }
}
