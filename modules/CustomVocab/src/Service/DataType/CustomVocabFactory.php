<?php
namespace CustomVocab\Service\DataType;

use CustomVocab\DataType\CustomVocab;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Omeka\Api\Exception\NotFoundException;

class CustomVocabFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $services, $requestedName)
    {
        // The service manager can create a CustomVocab service if the name
        // matches the "customvocab:<id>" pattern and if the corresponding
        // custom vocabulary exists in the database.
        $isValidName = (bool) preg_match('/^customvocab:(\d+)$/', $requestedName, $matches);
        if (!$isValidName) {
            return false;
        }
        try {
            $services->get('Omeka\ApiManager')->read('custom_vocabs', $matches[1]);
        } catch (NotFoundException $e) {
            return false;
        }
        return true;
    }

    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // Derive the custom vocab ID, fetch the representation, and pass it to
        // the data type.
        $id = (int) substr($requestedName, strrpos($requestedName, ':') + 1);
        $vocab = $services->get('Omeka\ApiManager')->read('custom_vocabs', $id)->getContent();
        return new CustomVocab($vocab);
    }
}
