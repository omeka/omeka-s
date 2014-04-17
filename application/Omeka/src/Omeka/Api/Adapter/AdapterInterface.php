<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * API adapter interface.
 */
interface AdapterInterface extends
    ServiceLocatorAwareInterface,
    EventManagerAwareInterface,
    ResourceInterface
{
    /**
     * Search a set of resources.
     *
     * @param mixed $data
     * @return mixed
     */
    public function search($data = null);

    /**
     * Create a resource.
     *
     * @param mixed $data
     * @return mixed
     */
    public function create($data = null);

    /**
     * Batch create resources.
     *
     * Adapters implementing this operation should return the resultant
     * resources as the response content so the create.pre and create.post
     * events can be triggered for every resource.
     *
     * @param mixed $data
     * @return mixed
     */
    public function batchCreate($data = null);

    /**
     * Read a resource.
     *
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function read($id, $data = null);

    /**
     * Update a resource.
     *
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function update($id, $data = null);

    /**
     * Delete a resource.
     *
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function delete($id, $data = null);

    /**
     * Get the URL to an API representation.
     *
     * @param mixed $resource
     * @return null|string
     */
    public function getApiUrl($resource);

    /**
     * Get the URL to a web representation.
     *
     * @param mixed $resource
     * @return null|string
     */
    public function getWebUrl($resource);

    /**
     * Set the API request.
     *
     * @param Request $request
     */
    public function setRequest(Request $request);

    /**
     * Get the API request.
     *
     * @return Request
     */
    public function getRequest();
}
