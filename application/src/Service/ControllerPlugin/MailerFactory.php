<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Mailer;
use Zend\ServiceManager\Factory\FactoryInterface;

class MailerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Mailer($services->get('Omeka\Mailer'));
    }
}
