<?php
namespace Omeka\Service\File;

use Omeka\File\Validator;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ValidatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        return new Validator(
            $settings->get('media_type_whitelist', []),
            $settings->get('extension_whitelist', []),
            $settings->get('disable_file_validation', false)
        );
    }
}
