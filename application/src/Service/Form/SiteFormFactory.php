<?php
namespace Omeka\Service\Form;

use Omeka\Form\SiteForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SiteFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SiteForm(null, $options);
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
