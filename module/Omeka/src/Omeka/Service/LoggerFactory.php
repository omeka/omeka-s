<?php
namespace Omeka\Service;

use Zend\Log\Logger;
use Zend\Log\Writer\Null as NullWriter;
use Zend\Log\Writer\Stream;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Logger factory.
 */
class LoggerFactory implements FactoryInterface
{
    /**
     * Create the logger service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Logger
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('ApplicationConfig');
        if (isset($config['logger']['log'])
            && $config['logger']['log']
            && isset($config['logger']['path'])
            && is_file($config['logger']['path'])
            && is_writable($config['logger']['path'])
        ) {
            $writer = new Stream($config['logger']['path']);
        } else {
            $writer = new NullWriter;
        }
        $logger = new Logger;
        $logger->addWriter($writer);
        return $logger;
    }
}
