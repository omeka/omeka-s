<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\ResourceInterface;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Resource as ResourceEntity;
use Omeka\Stdlib\ErrorStore;

class ResourceAdapter extends AbstractResourceEntityAdapter
{
    public function getResourceName()
    {
        return 'resources';
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\Resource::class;
    }

    public function getRepresentationClass()
    {
        return null;
    }

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
     * @return ResourceInterface|null
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

    public function create(Request $request)
    {
        $content = $request->getContent();
        if (empty($content['@type'])) {
            throw new Exception\BadRequestException(
                $this->getTranslator()->translate('The resource type must be set as "@type" when creating a generic resource.') // @translate
            );
        }
        $adapterManager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
        $contentTypesToResourceNames = [
            'o:ItemSet' => 'item_sets',
            'o:Item' => 'items',
            'o:Media' => 'media',
        ];
        if (is_array($content['@type'])) {
            $resourceName = array_intersect_key($contentTypesToResourceNames, array_flip($content['@type']));
            if (!$resourceName) {
                throw new Exception\BadRequestException(
                    $this->getTranslator()->translate('The generic creation of a resource is not managed for content type.') // @translate
                );
            }
            $resourceName = reset($resourceName);
        } elseif (!isset($contentTypesToResourceNames[$content['@type']])) {
            throw new Exception\BadRequestException(sprintf(
                $this->getTranslator()->translate('The generic creation of a resource is not managed for content type "%s".'), // @translate
                $content['@type']
            ));
        } else {
            $resourceName = $contentTypesToResourceNames[$content['@type']];
        }
        return $adapterManager->get($resourceName)->create($request);
    }

    public function update(Request $request)
    {
        AbstractAdapter::batchCreate($request);
    }

    public function delete(Request $request)
    {
        AbstractAdapter::delete($request);
    }
}
