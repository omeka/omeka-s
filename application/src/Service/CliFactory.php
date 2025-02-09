<?php
namespace Omeka\Service;

use Omeka\Stdlib\Cli;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Omeka\Service\Exception\RuntimeException;

class CliFactory implements FactoryInterface
{
    /**
     * Create the CLI service.
     *
     * @return Cli
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        $config = $serviceLocator->get('Config');

        $strategy = null;
        if (isset($config['cli']['execute_strategy'])) {
            $strategy = $config['cli']['execute_strategy'];
            if ($strategy === 'auto') {
                $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));
                if (function_exists('proc_open') && !in_array('proc_open', $disabledFunctions)) {
                    $strategy = 'proc_open';
                } elseif (function_exists('exec') && !in_array('exec', $disabledFunctions)) {
                    $strategy = 'exec';
                } else {
                    throw new RuntimeException('Neither "proc_open()" nor "exec()" are available.'); // @translate
                }
            }
        }

        return new Cli($logger, $strategy);
    }
}
