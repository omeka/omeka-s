<?php
namespace CustomVocab\Service\DatascribeDataType;

use Interop\Container\ContainerInterface;
use CustomVocab\DatascribeDataType\CustomVocabSelect;
use Zend\ServiceManager\Factory\FactoryInterface;

class CustomVocabSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new CustomVocabSelect($services->get('Omeka\ApiManager'));
    }
}
