<?php declare(strict_types=1);

namespace Common\Service;

use Common\Log\Formatter\PsrLogSimple as PsrLogSimpleFormatter;
use Interop\Container\ContainerInterface;
use Laminas\Log\Filter\Priority;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Noop;
use Laminas\Log\Writer\Stream;
use Laminas\ServiceManager\Factory\FactoryInterface;

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
            && is_writeable($config['logger']['path'])
        ) {
            $writer = new Stream($config['logger']['path']);
            $writer->setFormatter(new PsrLogSimpleFormatter());
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
