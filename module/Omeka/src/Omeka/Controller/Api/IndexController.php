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
        $apiRequest = new ApiRequest(ApiRequest::METHOD_CREATE, 'items');
        $apiResponse = $this->getServiceLocator()->get('ApiManager')->respond($apiRequest);
        
        //~ new ApiRequest(ApiRequest::METHOD_READ, 'items');
        //~ new ApiRequest(ApiRequest::METHOD_READ, 'items', 1);
        //~ new ApiRequest(ApiRequest::METHOD_UPDATE, 'items', 1);
        //~ new ApiRequest(ApiRequest::METHOD_DELETE, 'items', 1);
        
        return new ViewModel();
    }
}
