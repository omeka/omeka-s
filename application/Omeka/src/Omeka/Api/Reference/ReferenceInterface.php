<?php
namespace Omeka\Api\Reference;

use Omeka\Api\Adapter\AdapterInterface;
use Zend\Stdlib\JsonSerializable;

/**
 * A reference to a resource.
 */
interface ReferenceInterface extends JsonSerializable
{
    /**
     * Set the reference data
     *
     * @param mixed $data The information from which to derive a representation
     * of the resource.
     */
    public function setData($data);

    /**
     * Set the API adapter
     *
     * @param AdapterInterface $adapters The corresponding API adapter for this
     * resource.
     */
    public function setAdapter(AdapterInterface $adapters);

    /**
     * Get the URL to this resource's API representation
     *
     * @see AdapterInterface::getApiUrl()
     * @return string
     */
    public function getApiUrl();

    /**
     * Get the URL to this resource's web representation
     *
     * @see AdapterInterface::getWebUrl()
     * @return string
     */
    public function getWebUrl();

    /**
     * Serialize the resource as an array
     *
     * @return mixed
     */
    public function toArray();
}
