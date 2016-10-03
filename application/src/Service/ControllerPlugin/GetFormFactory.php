<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\GetForm;
use Zend\ServiceManager\Factory\FactoryInterface;

class GetFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new GetForm($services->get('FormElementManager'));
    }
}
