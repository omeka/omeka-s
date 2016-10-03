<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\ViewHelpers;
use Zend\ServiceManager\Factory\FactoryInterface;

class ViewHelpersFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ViewHelpers($services->get('ViewHelperManager'));
    }
}
