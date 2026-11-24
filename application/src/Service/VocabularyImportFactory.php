<?php
namespace Omeka\Service;

use Omeka\Stdlib\VocabularyImport;
use Omeka\Stdlib\WorkflowStates;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class VocabularyImportFactory implements FactoryInterface
{
    /**
     * Create the vocabulary import service.
     *
     * @return VocabularyImport
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new VocabularyImport(
            $serviceLocator->get('Omeka\RdfImporter'),
            $serviceLocator->get('Omeka\File\Stager'),
            $serviceLocator->get('Router'),
            $serviceLocator->get('Omeka\ApiAdapterManager')->get('vocabularies'),
            new WorkflowStates('OmekaVocabularyImport')
        );
    }
}
