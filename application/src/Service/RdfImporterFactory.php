<?php
namespace Omeka\Service;

use Omeka\Stdlib\RdfImporter;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class RdfImporterFactory implements FactoryInterface
{
    /**
     * Create the RDF importer service.
     *
     * @return RdfImporter
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new RdfImporter(
            $serviceLocator->get('Omeka\ApiManager'),
            $serviceLocator->get('Omeka\EntityManager')
        );
    }
}
