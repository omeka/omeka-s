<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager;
use Omeka\Api\Response;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Form;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for providing passthrough methods to the API manager.
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

    /**
     * @var bool
     */
    protected $throwValidationException = false;

    /**
     * Construct the plugin.
     *
     * @param Manager $api
     */
    public function __construct(Manager $api)
    {
        $this->api = $api;
    }

    /**
     * Set this API request's corresponding form, if any.
     *
     * @param null|Form $form
     * @param bool $throwValidationException
     */
    public function __invoke(Form $form = null, $throwValidationException = false)
    {
        $this->form = $form;
        $this->throwValidationException = $throwValidationException;
        return $this;
    }

    /**
     * Execute a search API request.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function search($resource, $data = [], array $options = [])
    {
        return $this->api->search($resource, $data, $options);
    }

    /**
     * Execute a search API request and get the first result.
     *
     * Sets the first result to the response content or null if there is no
     * result. Note that this functionality is not native to the API.
     *
     * @param string $resource
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function searchOne($resource, $data = [], array $options = [])
    {
        $data['limit'] = 1;
        $response = $this->search($resource, $data, $options);
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
     * @param array $fileData
     * @param array $options
     * @return Response|false Returns false on validation error
     */
    public function create($resource, $data = [], $fileData = [], array $options = [])
    {
        try {
            return $this->api->create($resource, $data, $fileData, $options);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            return false;
        }
    }

    /**
     * Execute a batch create API request.
     *
     * @param string $resource
     * @param array $data
     * @param array $fileData
     * @param array $options
     * @return Response|false Returns false on validation error
     */
    public function batchCreate($resource, $data = [], $fileData = [], array $options = [])
    {
        try {
            return $this->api->batchCreate($resource, $data, $fileData, $options);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            return false;
        }
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function read($resource, $id, $data = [], array $options = [])
    {
        return $this->api->read($resource, $id, $data, $options);
    }

    /**
     * Execute an update API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $fileData
     * @param array $options
     * @return Response|false Returns false on validation error
     */
    public function update($resource, $id, $data = [], $fileData = [], array $options = [])
    {
        try {
            return $this->api->update($resource, $id, $data, $fileData, $options);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            return false;
        }
    }

    /**
     * Execute a batch update API request.
     *
     * @param string $resource
     * @param array $ids
     * @param array $data
     * @param array $options
     * @return Response|false Returns false on validation error
     */
    public function batchUpdate($resource, array $ids, $data = [], array $options = [])
    {
        try {
            return $this->api->batchUpdate($resource, $ids, $data, $options);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            return false;
        }
    }

    /**
     * Execute a delete API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function delete($resource, $id, $data = [], array $options = [])
    {
        return $this->api->delete($resource, $id, $data, $options);
    }

    /**
     * Execute a batch delete API request.
     *
     * @param string $resource
     * @param array $ids
     * @param array $data
     * @param array $options
     * @return Response|false Returns false on validation error
     */
    public function batchDelete($resource, array $ids, array $data = [], array $options = [])
    {
        try {
            return $this->api->batchDelete($resource, $ids, $data, $options);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            return false;
        }
    }

    /**
     * Handle an API validation exception.
     *
     * @throws ValidationException
     * @param ErrorStore $errorStore
     */
    public function handleValidationException(ValidationException $e)
    {
        $errorStore = $e->getErrorStore();
        if ($this->form) {
            $formMessages = [];
            foreach ($errorStore->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    // Do not set nested errors to the form.
                    if (!is_array($message)) {
                        $formMessages[$key][] = $this->getController()->translate($message);
                    }
                }
            }
            $this->form->setMessages($formMessages);
            $this->getController()->messenger()->addErrors($errorStore->getErrors());
        }
        if ($this->throwValidationException) {
            throw $e;
        }
    }
}
