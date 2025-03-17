<?php
namespace Collecting\Service\MediaType;

use Collecting\MediaType\Upload;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UploadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $request = $services->get('Request');
        return new Upload($request);
    }
}
