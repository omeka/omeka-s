<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Api\ResourceInterface as ApiResourceInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API adapter interface.
 */
interface AdapterInterface extends EventManagerAwareInterface, ResourceInterface
{
    /**
     * Get the name of the corresponding API resource.
     *
     * @return string
     */
    public function getResourceName();

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
     * Batch update a resource.
     *
     * @param Request $request
     * @return Response
     */
    public function batchUpdate(Request $request);

    /**
     * Delete a resource.
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request);

    /**
     * Compose a resource representation object.
     *
     * @param ApiResourceInterface $data Data needed to compose the representation.
     * @return RepresentationInterface|null
     */
    public function getRepresentation(ApiResourceInterface $data = null);

    /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator);

    /**
     * Get the service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator();
}
