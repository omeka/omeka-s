<?php
namespace Omeka\Service\Form;

use Omeka\Form\UserForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UserFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new UserForm(null, $options);

        $form->setAcl($services->get('Omeka\Acl'));
        $form->setUserSettings($services->get('Omeka\Settings\User'));
        $form->setSettings($services->get('Omeka\Settings'));
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
