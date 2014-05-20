<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;

class Representation extends AbstractRepresentation
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
        $this->setId($id);
        $this->setData($data);
        $this->setAdapter($adapter);
        $this->setServiceLocator($adapter->getServiceLocator());
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
    protected function getId()
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

    /**
     * {@inheritDoc}
     */
    public function extract()
    {
        return $this->getData();
    }

    /**
     * Serialize as a simple JSON-LD object.
     *
     * Typically used for simple representations, such as references. Override
     * this method to compose a more complex JSON-LD object.
     *
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array(
            '@id' => $this->getAdapter()->getApiUrl($this->getData()),
            'id'  => $this->getId(),
        );
    }
}
