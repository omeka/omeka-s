<?php
namespace Omeka\Controller\Api;

use Omeka\Api\Request as ApiRequest;
use Omeka\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractRestfulController
{
    public function indexAction()
    {
        $apiManager = $this->getServiceLocator()->get('ApiManager');
        $apiRequest = new ApiRequest(
            ApiRequest::FUNCTION_SEARCH, 
            $this->params()->fromRoute('resource')
        );
        $apiResponse = $apiManager->execute($apiRequest);
        return new ViewModel();
    }
}
