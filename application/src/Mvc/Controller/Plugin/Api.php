<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Provide passthrough methods to the API manager.
 */
class Api extends AbstractPlugin
{
    /**
     * Execute a search API request.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function search($resource, $data = array())
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->search($resource, $data);
    }

    /**
     * Execute a search API request and get the first result.
     *
     * Sets the first result to the response content or null if there is no
     * result. Note that this functionality is not native to the API.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function searchOne($resource, $data = array())
    {
        $data['limit'] = 1;
        $response = $this->search($resource, $data);
        $content = $response->getContent();
        $content = is_array($content) && count($content) ? $content[0] : null;
        $response->setContent($content);
        return $response;
    }

    /**
     * Execute a create API request.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function create($resource, $data = array())
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->create($resource, $data);
    }

    /**
     * Execute a batch create API request.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function batchCreate($resource, $data = array())
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->batchCreate($resource, $data);
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @return Response
     */
    public function read($resource, $id, $data = array())
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->read($resource, $id, $data);
    }

    /**
     * Execute an update API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @return Response
     */
    public function update($resource, $id, $data = array())
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->update($resource, $id, $data);
    }

    /**
     * Execute a delete API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @return Response
     */
    public function delete($resource, $id, $data = array())
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->delete($resource, $id, $data);
    }
}
