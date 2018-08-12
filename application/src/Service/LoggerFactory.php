<?php
namespace Omeka\Service;

use Interop\Container\ContainerInterface;
use Zend\Log\Logger;
use Zend\Log\Writer\Noop;
use Zend\ServiceManager\Factory\FactoryInterface;

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

        if (empty($config['logger']['log'])) {
            return (new Logger)->addWriter(new Noop);
        }

        $enabledWriters = array_filter($config['logger']['writers']);
        $writers = array_intersect_key($config['logger']['options']['writers'], $enabledWriters);
        if (empty($writers)) {
            return (new Logger)->addWriter(new Noop);
        }

        // Trigger error for compatibility with a customized local config (Omeka S 1.0).
        if (!empty($writers['stream'])
            && (isset($config['logger']['priority']) || isset($config['logger']['path']))
        ) {
            if (isset($config['logger']['priority'])) {
                $writers['stream']['options']['filters'] = $config['logger']['priority'];
            }
            if (isset($config['logger']['path'])) {
                $writers['stream']['options']['stream'] = $config['logger']['path'];
            }
            trigger_error(
                'Update your config/local.config.php for Omeka S 1.3 (logger path or priority).',
                E_USER_DEPRECATED
            );
        }

        $config['logger']['options']['writers'] = $writers;

        // Checks are managed via the constructor.
        return new Logger($config['logger']['options']);
    }
}
