<?php
namespace Omeka\Service\Form;

use Omeka\Form\UserForm;
use Zend\Form\ElementFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $elementFactory = new ElementFactory;
        $form = $elementFactory($elements, UserForm::class, $this->options);
        $form->setAcl($elements->getServiceLocator()->get('Omeka\Acl'));
        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
