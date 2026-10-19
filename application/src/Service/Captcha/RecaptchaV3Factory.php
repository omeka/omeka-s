<?php
namespace Omeka\Service\Captcha;

use Omeka\Captcha\RecaptchaV3;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class RecaptchaV3Factory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new RecaptchaV3($services->get('Omeka\Settings'), $services->get('Omeka\HttpClient'));
    }
}
