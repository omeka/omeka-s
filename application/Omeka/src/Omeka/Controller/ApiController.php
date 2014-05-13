<?php
namespace Omeka\Controller;

use Omeka\Api\Response;
use Omeka\Api\Request;
use Omeka\View\Model\ApiJsonModel;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;

class ApiController extends AbstractRestfulController
{
    /**
     * @var ApiJsonModel
     */
    protected $viewModel;

    public function get($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->read($resource, $id);
        $this->viewModel->setApiResponse($response);
        return $this->viewModel;
    }

    public function getList()
    {
        $resource = $this->params()->fromRoute('resource');
        $data = $this->params()->fromQuery();
        $response = $this->api()->search($resource, $data);
        $this->viewModel->setApiResponse($response);
        return $this->viewModel;
    }

    public function create($data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->create($resource, $data);
        $this->viewModel->setApiResponse($response);
        return $this->viewModel;
    }

    public function update($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->update($resource, $id, $data);
        $this->viewModel->setApiResponse($response);
        return $this->viewModel;
    }

    public function delete($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->delete($resource, $id);
        $this->viewModel->setApiResponse($response);
        return $this->viewModel;
    }

    public function attachDefaultListeners()
    {
        $events = $this->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'setViewModel'));
        parent::attachDefaultListeners();
    }

    /**
     * Set the API JSON view model and its options.
     *
     * @param MvcEvent $e
     */
    public function setViewModel(MvcEvent $e)
    {
        $viewModel = new ApiJsonModel;

        $prettyPrint = $this->getRequest()->getQuery('pretty_print');
        if (null !== $prettyPrint) {
            $viewModel->setOption('pretty_print', true);
        }

        $callback = $this->getRequest()->getQuery('callback');
        if (null !== $callback) {
            $viewModel->setOption('callback', $callback);
        }

        $this->viewModel = $viewModel;
    }
}
