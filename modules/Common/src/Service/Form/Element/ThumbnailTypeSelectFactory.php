<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\ThumbnailTypeSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ThumbnailTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $types = $services->get(\Omeka\File\ThumbnailManager::class)->getTypes();
        $element = new ThumbnailTypeSelect(null, $options ?? []);
        return $element
            ->setValueOptions(array_combine($types, $types));
    }
}
