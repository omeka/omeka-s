<?php declare(strict_types=1);

namespace Common\Service\ControllerPlugin;

use Common\Mvc\Controller\Plugin\EasyMeta;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class EasyMetaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new EasyMeta(
            $services->get('Common\EasyMeta')
        );
    }
}
