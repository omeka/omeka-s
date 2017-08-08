<?php
namespace Omeka\Service\Form;

use Omeka\Form\SiteSettingsForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SiteSettingsFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SiteSettingsForm;
        $form->setSiteSettings($services->get('Omeka\Settings\Site'));
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
