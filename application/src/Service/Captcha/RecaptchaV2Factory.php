<?php
namespace Omeka\Service\Captcha;

use Omeka\Captcha\RecaptchaV2;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class RecaptchaV2Factory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new RecaptchaV2($services->get('Omeka\Settings'), $services->get('Omeka\HttpClient'));
    }
}
