<?php
namespace Omeka\Service\Form;

use Omeka\Form\SiteForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SiteFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SiteForm;
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
