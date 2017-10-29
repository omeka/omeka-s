<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Api\ResourceInterface;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Resource as ResourceEntity;
use Omeka\Stdlib\ErrorStore;

class ResourceAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'resources';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Resource';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
    }

    /**
     * Compose a resource representation object.
     *
     * This version simply proxies to the "real" getRepresentation for each resource's adapter.
     *
     * @param string|int $id The unique identifier of the resource
     * @param mixed $data Whatever data is needed to compose the representation.
     * @return RepresentationInterface|null
     */
    public function getRepresentation(ResourceInterface $data = null)
    {
        if (!$data instanceof ResourceEntity) {
            // Do not attempt to compose a null representation.
            return null;
        }

        $adapter = $this->getAdapter($data->getResourceName());
        return $adapter->getRepresentation($data);
    }

    /**
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        AbstractAdapter::search($request);
    }

    /**
     * {@inheritDoc}
     */
    public function create(Request $request)
    {
        AbstractAdapter::create($request);
    }

    /**
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        AbstractAdapter::batchCreate($request);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Request $request)
    {
        AbstractAdapter::batchCreate($request);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        AbstractAdapter::delete($request);
    }
}
