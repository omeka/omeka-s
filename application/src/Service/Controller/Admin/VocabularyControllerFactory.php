<?php
namespace Omeka\Service\Controller\Admin;

use Omeka\Controller\Admin\VocabularyController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class VocabularyControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new VocabularyController($services->get('Omeka\RdfImporter'));
    }
}
