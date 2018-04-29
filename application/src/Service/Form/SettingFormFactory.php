<?php
namespace Omeka\Service\Form;

use Omeka\Form\SettingForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SettingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SettingForm;
        $form->setSettings($services->get('Omeka\Settings'));
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
