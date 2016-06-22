<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\Paginator;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PaginatorFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Paginator($plugins->getServiceLocator()->get('ViewHelperManager'));
    }
}
