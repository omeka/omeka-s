<?php
namespace Omeka\Controller\Api;

use Omeka\Controller\AbstractRestfulController;
use Omeka\Api\Request as ApiRequest;
use Zend\View\Model\ViewModel;
use Doctrine\Common\Util\Debug;

class IndexController extends AbstractRestfulController
{
    public function indexAction()
    {
        $apiManager = $this->getServiceLocator()->get('ApiManager');
        $apiRequest = new ApiRequest(
            ApiRequest::FUNCTION_SEARCH, 
            $this->params()->fromRoute('resource')
        );
        $apiResponse = $apiManager->respond($apiRequest);
        return new ViewModel();
    }
}
