<?php
namespace Collecting\Service\MediaType;

use Collecting\MediaType\UploadMultiple;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UploadMultipleFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $request = $services->get('Request');
        return new UploadMultiple($request);
    }
}
