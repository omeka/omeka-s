<?php
namespace Omeka\Service\Settings;

use Omeka\Settings\UserSettings;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UserSettingsFactory implements FactoryInterface
{
    /**
     * Create the user settings service.
     *
     * @return UserSettings
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new UserSettings(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\Status')
        );
    }
}
