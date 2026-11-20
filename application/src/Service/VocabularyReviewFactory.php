<?php
namespace Omeka\Service;

use Omeka\Stdlib\VocabularyReview;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class VocabularyReviewFactory implements FactoryInterface
{
    /**
     * Create the vocabulary review service.
     *
     * @return VocabularyReview
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new VocabularyReview(
            $serviceLocator->get('Omeka\RdfImporter'),
            $serviceLocator->get('Omeka\File\StagedFiles'),
            $serviceLocator->get('Omeka\ApiManager'),
            $serviceLocator->get('Omeka\EntityManager'),
            $serviceLocator->get('Router'),
            $serviceLocator->get('Omeka\ApiAdapterManager')->get('vocabularies')
        );
    }
}
