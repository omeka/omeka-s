<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DeleteConfirm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DeleteConfirmFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new DeleteConfirm($services->get('FormElementManager'));
    }
}
