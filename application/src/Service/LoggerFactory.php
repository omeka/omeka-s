<?php
namespace Omeka\Service;

use Laminas\Log\Logger;
use Laminas\Log\Writer\Noop;
use Laminas\Log\Writer\Stream;
use Laminas\Log\Filter\Priority;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Logger factory.
 */
class LoggerFactory implements FactoryInterface
{
    /**
     * Create the logger service.
     *
     * @return Logger
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (isset($config['logger']['log'])
            && $config['logger']['log']
            && isset($config['logger']['path'])
            && is_file($config['logger']['path'])
            && is_writable($config['logger']['path'])
        ) {
            $writer = new Stream($config['logger']['path']);
        } else {
            $writer = new Noop;
        }
        $logger = new Logger;
        $logger->addWriter($writer);
        $filter = new Priority($config['logger']['priority']);
        $writer->addFilter($filter);
        return $logger;
    }
}
