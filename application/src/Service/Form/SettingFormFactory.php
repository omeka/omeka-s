<?php
namespace Omeka\Service\Form;

use Omeka\Form\SettingForm;
use Zend\Form\ElementFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SettingFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $elementFactory = new ElementFactory;
        $form = $elementFactory($elements, SettingForm::class, $this->options);
        $form->setSettings($elements->getServiceLocator()->get('Omeka\Settings'));
        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
