<?php
namespace Omeka\Service\Form;

use Interop\Container\ContainerInterface;
use Omeka\Form\UserBatchUpdateForm;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserBatchUpdateFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new UserBatchUpdateForm(null, $options);
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
