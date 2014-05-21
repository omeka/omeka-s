<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;

/**
 * Abstract API resource representation.
 *
 * Provides functionality for representations of registered API resources.
 */
abstract class AbstractResourceRepresentation extends AbstractRepresentation
{
    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Construct the resource representation object.
     *
     * @param string|int $id The unique identifier of this resource
     * @param mixed $data The data from which to derive a representation
     * @param ServiceLocatorInterface $adapter The corresponsing adapter
     */
    public function __construct($id, $data, AdapterInterface $adapter) {
        // Set the service locator first.
        $this->setServiceLocator($adapter->getServiceLocator());
        $this->setId($id);
        $this->setData($data);
        $this->setAdapter($adapter);
    }

    /**
     * Set the unique resource identifier.
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the unique resource identifier.
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the corresponding adapter.
     *
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the corresponding adapter or another adapter by resource name.
     *
     * @param null|string $resourceName
     * @return AdapterInterface
     */
    public function getAdapter($resourceName = null)
    {
        if (is_string($resourceName)) {
            return parent::getAdapter($resourceName);
        }
        return $this->adapter;
    }
}
