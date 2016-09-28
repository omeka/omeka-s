<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Manager;
use Omeka\Api\Response;
use Zend\Form\Form;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Provide passthrough methods to the API manager.
 */
class Api extends AbstractPlugin
{
    /**
     * @var Manager
     */
    protected $api;

    /**
     * @var Form
     */
    protected $form;

    public function __construct(Manager $api)
    {
        $this->api = $api;
    }

    /**
     * Set this API request's corresponding form, if any.
     *
     * @param null|Form $form
     */
    public function __invoke(Form $form = null)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Execute a search API request.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function search($resource, $data = [])
    {
        $response = $this->api->search($resource, $data);
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
    public function searchOne($resource, $data = [])
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
     * @param array $files
     * @return Response
     */
    public function create($resource, $data = [], $fileData = [])
    {
        $response = $this->api->create($resource, $data, $fileData);
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
    public function batchCreate($resource, $data = [])
    {
        $response = $this->api->batchCreate($resource, $data);
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
    public function read($resource, $id, $data = [])
    {
        $response = $this->api->read($resource, $id, $data);
        $this->detectError($response);
        return $response;
    }

    /**
     * Execute an update API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $fileData
     * @param bool $partial
     * @return Response
     */
    public function update($resource, $id, $data = [], $fileData = [],
        $partial = false
    ) {
        $response = $this->api->update($resource, $id, $data,
            $fileData, $partial);
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
    public function delete($resource, $id, $data = [])
    {
        $response = $this->api->delete($resource, $id, $data);
        $this->detectError($response);
        return $response;
    }

    /**
     * Detect and account for API response errors.
     *
     * @param Response $response
     */
    public function detectError(Response $response)
    {
        if ($this->form && $response->getStatus() === Response::ERROR_VALIDATION) {
            $formMessages = [];
            foreach ($response->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    // Do not set nested errors to the form.
                    if (!is_array($message)) {
                        $formMessages[$key][] = $this->getController()->translate($message);
                    }
                }
            }
            $this->form->setMessages($formMessages);
            $this->getController()->messenger()->addErrors($response->getErrors());
        }
    }
}
