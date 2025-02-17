<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\CustomVocabsSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CustomVocabsSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // It is simpler to use a request than the datatype manager, that does
        // not output info about custom vocab types.
        // And this request is already available through EasyMeta.
        $element = new CustomVocabsSelect(null, $options ?? []);
        return $element
            ->setEasyMeta($services->get('Common\EasyMeta'));
    }
}
