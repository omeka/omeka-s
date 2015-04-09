<?php
namespace Omeka\Service;

use Zend\Log\Logger;
use Zend\Log\Writer\Noop;
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
        $config = $serviceLocator->get('Config');
        if (isset($config['loggers']['application']['log'])
            && $config['loggers']['application']['log']
            && isset($config['loggers']['application']['path'])
            && is_file($config['loggers']['application']['path'])
            && is_writable($config['loggers']['application']['path'])
        ) {
            $writer = new Stream($config['loggers']['application']['path']);
        } else {
            $writer = new Noop;
        }
        $logger = new Logger;
        $logger->addWriter($writer);
        return $logger;
    }
}
