<?php
namespace Omeka\Service\File;

use Omeka\File\Stager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class StagerFactory implements FactoryInterface
{
    /**
     * Create the stager of files for a later request.
     *
     * @return Stager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new Stager($serviceLocator->get('Config')['temp_dir']);
    }
}
