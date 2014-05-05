<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Api\Response;
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
     * @param Request $request
     * @return Response
     */
    public function search(Request $request);

    /**
     * Create a resource.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request);

    /**
     * Batch create resources.
     *
     * Adapters implementing this operation should return the resultant
     * resources as the response content so the create.pre and create.post
     * events can be triggered for every resource.
     *
     * @param Request $request
     * @return Response
     */
    public function batchCreate(Request $request);

    /**
     * Read a resource.
     *
     * @param Request $request
     * @return Response
     */
    public function read(Request $request);

    /**
     * Update a resource.
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request);

    /**
     * Delete a resource.
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request);

    /**
     * Get the URL to an API representation.
     *
     * @param mixed $data Whatever data is needed to construct the API URL.
     * @return null|string
     */
    public function getApiUrl($data);

    /**
     * Get the URL to a web representation.
     *
     * @param mixed $data Whatever data is needed to construct the web URL.
     * @return null|string
     */
    public function getWebUrl($data);
}
