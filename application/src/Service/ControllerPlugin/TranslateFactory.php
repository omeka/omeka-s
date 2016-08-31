<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Translate;
use Zend\ServiceManager\Factory\FactoryInterface;

class TranslateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Translate($services->get('MvcTranslator'));
    }
}
