<?php declare(strict_types=1);

namespace Common\Service\ViewHelper;

use Common\View\Helper\MediaTypeSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MediaTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new MediaTypeSelect($services->get('FormElementManager'));
    }
}
