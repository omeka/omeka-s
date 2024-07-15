<?php
namespace Omeka\Service\Form;

use Omeka\Form\ItemStubForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ItemStubFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ItemStubForm;
        $form->setApiManager($services->get('Omeka\ApiManager'));
        $form->setViewHelperManager($services->get('ViewHelperManager'));
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
