<?php
namespace Omeka\Service;

use Omeka\Stdlib\Mailer;
use Zend\Mail\Transport\Factory as TransportFactory;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MailerFactory implements FactoryInterface
{
    /**
     * Create the mailer service.
     *
     * @return Mialer
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        $viewHelpers = $serviceLocator->get('ViewHelperManager');
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        if (!isset($config['mail']['transport'])) {
            throw new Exception\ConfigException('Missing mail transport configuration');
        }
        $transport = TransportFactory::create($config['mail']['transport']);
        $defaultOptions = [];
        if (isset($config['mail']['default_message_options'])) {
            $defaultOptions = $config['mail']['default_message_options'];
        }
        if (!isset($defaultOptions['administrator_email'])) {
            $settings = $serviceLocator->get('Omeka\Settings');
            $defaultOptions['from'] = $settings->get('administrator_email');
        }
        return new Mailer($transport, $viewHelpers, $entityManager, $defaultOptions);
    }
}
