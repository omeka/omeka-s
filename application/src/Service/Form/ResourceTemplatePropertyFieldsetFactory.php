<?php
namespace Omeka\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Form\ResourceTemplatePropertyFieldset;

class ResourceTemplatePropertyFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ResourceTemplatePropertyFieldset(null, $options);
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
