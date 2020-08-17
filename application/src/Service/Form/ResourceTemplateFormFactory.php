<?php
namespace Omeka\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Form\ResourceTemplateForm;

class ResourceTemplateFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ResourceTemplateForm(null, $options);
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
