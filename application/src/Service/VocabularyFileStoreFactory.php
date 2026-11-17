<?php
namespace Omeka\Service;

use Omeka\Stdlib\VocabularyFileStore;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class VocabularyFileStoreFactory implements FactoryInterface
{
    /**
     * Create the store of vocabulary files kept between requests.
     *
     * @return VocabularyFileStore
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new VocabularyFileStore($serviceLocator->get('Config')['temp_dir']);
    }
}
