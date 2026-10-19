<?php
namespace Omeka\Service\Captcha;

use Omeka\Captcha\Hcaptcha;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HcaptchaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new Hcaptcha($services->get('Omeka\Settings'), $services->get('Omeka\HttpClient'));
    }
}
