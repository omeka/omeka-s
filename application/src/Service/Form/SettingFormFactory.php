<?php
namespace Omeka\Service\Form;

use Omeka\Form\SettingForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SettingFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new SettingForm;
        $form->setSettings($elements->getServiceLocator()->get('Omeka\Settings'));
        return $form;
    }
}
