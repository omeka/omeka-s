<?php
namespace Omeka\Service\Form;

use Omeka\Form\AssetForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class AssetFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $form = new AssetForm;

        return $form;
    }
}
