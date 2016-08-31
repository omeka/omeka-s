<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ItemSetSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ItemSetSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        return new ItemSetSelect($formElementManager);
    }
}
