<?php
namespace Omeka\Service\Form;

use Omeka\Form\UserForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new UserForm(null, $this->options);
        $form->setAcl($elements->getServiceLocator()->get('Omeka\Acl'));
        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
