<?php declare(strict_types=1);

namespace CustomVocab\Service\Form\Element;

use CustomVocab\Form\Element\CustomVocabSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CustomVocabSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $select = new CustomVocabSelect(null, $options ?? []);
        return $select->setApiManager($services->get('Omeka\ApiManager'));
    }
}
