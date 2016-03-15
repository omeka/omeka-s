<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class RdfImporterFactory implements FactoryInterface
{
    /**
     * Create the RDF importer service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return RdfImporter
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new RdfImporter($serviceLocator->get('Omeka\ApiManager'));
    }
}
