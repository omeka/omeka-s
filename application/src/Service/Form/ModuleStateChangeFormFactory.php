<?php
namespace Omeka\Service\Form;

use Omeka\Form\ModuleStateChangeForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ModuleStateChangeFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new ModuleStateChangeForm(null, $this->options);
        $form->setUrlHelper($elements->getServiceLocator()->get('ViewHelperManager')->get('Url'));
        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
