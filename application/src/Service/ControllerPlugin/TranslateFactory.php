<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\Translate;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TranslateFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Translate($plugins->getServiceLocator()->get('MvcTranslator'));
    }
}
