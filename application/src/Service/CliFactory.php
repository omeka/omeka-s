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
                if ($this->functionIsEnabled('proc_open')) {
                    $strategy = 'proc_open';
                } elseif ($this->functionIsEnabled('exec')) {
                    $strategy = 'exec';
                } else {
                    throw new RuntimeException('Neither "proc_open()" nor "exec()" are available.'); // @translate
                }
            }
        }

        return new Cli($logger, $strategy);
    }

    /**
     * Check if a function is available.
     *
     * @param string $function
     * @return bool
     */
    protected function functionIsEnabled($function)
    {
        return function_exists($function)
            && !in_array($function, array_map('trim', explode(',', ini_get('disable_functions'))));
    }
}
