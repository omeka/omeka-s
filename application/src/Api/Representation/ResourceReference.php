<?php
namespace Omeka\Api\Representation;

use Omeka\Api\ResourceInterface;
use Omeka\Api\Adapter\AdapterInterface;

/**
 * A reference representation of an API resource.
 *
 * Provides the minimal representation of a resource.
 */
class ResourceReference extends AbstractRepresentation
{
    protected $id;

    protected $resourceName;

    public function __construct(ResourceInterface $resource, AdapterInterface $adapter)
    {
        $this->setServiceLocator($adapter->getServiceLocator());
        $this->id = $resource->getId();
        $this->resourceName = $adapter->getResourceName();
    }

    public function id()
    {
        return $this->id;
    }

    public function resourceName()
    {
        return $this->resourceName;
    }

    public function apiUrl()
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'api/default',
            [
                'resource' => $this->resourceName,
                'id' => $this->id,
            ],
            ['force_canonical' => true]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            '@id' => $this->apiUrl(),
            'o:id' => $this->id(),
        ];
    }
}
