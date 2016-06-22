<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\Mailer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MailerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Mailer($plugins->getServiceLocator()->get('Omeka\Mailer'));
    }
}
