<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\SiteSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SiteSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        return new SiteSelect($formElementManager);
    }
}
