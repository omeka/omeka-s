<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\VocabularyController;
use Zend\ServiceManager\Factory\FactoryInterface;

class VocabularyControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new VocabularyController($services->get('Omeka\RdfImporter'));
    }
}
