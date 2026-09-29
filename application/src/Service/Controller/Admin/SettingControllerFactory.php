<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\SettingController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SettingControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new SettingController($services->get('Omeka\CaptchaManager'));
    }
}
