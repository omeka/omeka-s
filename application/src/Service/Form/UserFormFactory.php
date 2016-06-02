<?php
namespace Omeka\Service\Form;

use Omeka\Form\UserForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserFormFactory implements FactoryInterface
{
    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function createService(ServiceLocatorInterface $elements)
    {
        $name = null;
        if (isset($this->options['name'])) {
            $name = $this->options['name'];
        }
        $userForm = new UserForm($name, $this->options);
        $userForm->setAcl($elements->getServiceLocator()->get('Omeka\Acl'));
        return $userForm;
    }
}
