<?php
namespace Omeka\Service\Form;

use Omeka\Form\ResourceStubForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceStubFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ResourceStubForm;
        $form->setUrlHelper($services->get('ViewHelperManager')->get('Url'));
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
