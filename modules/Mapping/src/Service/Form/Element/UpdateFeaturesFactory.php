<?php
namespace Mapping\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Mapping\Form\Element\UpdateFeatures;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UpdateFeaturesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new UpdateFeatures;
        $element->setFormElementManager($services->get('FormElementManager'));
        return $element;
    }
}
