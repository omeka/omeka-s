<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Response;
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
        $response = $this->getApiManager()->search($resource, $data);
        $this->detectError($response);
        return $response;
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
        $response = $this->getApiManager()->create($resource, $data);
        $this->detectError($response);
        return $response;
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
        $response = $this->getApiManager()->batchCreate($resource, $data);
        $this->detectError($response);
        return $response;
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
        $response = $this->getApiManager()->read($resource, $id, $data);
        $this->detectError($response);
        return $response;
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
        $response = $this->getApiManager()->update($resource, $id, $data);
        $this->detectError($response);
        return $response;
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
        $response = $this->getApiManager()->delete($resource, $id, $data);
        $this->detectError($response);
        return $response;
    }

    /**
     * Detect API response errors and account for them.
     *
     * @throws mixed
     * @return Response
     */
    protected function detectError(Response $response)
    {
        if ($e = $response->getException()) {
            // Rethrow exceptions
            throw $e;
        }

        if ($response->getStatus() === Response::ERROR_VALIDATION) {
            $this->getController()->messenger()
                ->addError('There was an error during validation');
        }
    }

    protected function getApiManager()
    {
        return $this->getController()->getServiceLocator()
            ->get('Omeka\ApiManager');
    }
}
