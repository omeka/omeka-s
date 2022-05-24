<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\PasswordRequirements;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PasswordRequirementsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $passwordConfig = $config['password'] ?? [];
        return new PasswordRequirements($passwordConfig);
    }
}
