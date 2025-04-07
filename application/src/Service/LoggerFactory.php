<?php
namespace Omeka\Service;

use Laminas\Log\Exception;
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
        ) {
            try {
                $writer = new Stream($config['logger']['path']);
            } catch (Exception\RuntimeException $e) {
                $writer = new Noop;
                error_log('Omeka S log initialization failed: ' . $e->getMessage());
            }
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
