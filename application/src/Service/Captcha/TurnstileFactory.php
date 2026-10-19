<?php
namespace Omeka\Service\Captcha;

use Omeka\Captcha\Turnstile;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class TurnstileFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new Turnstile($services->get('Omeka\Settings'), $services->get('Omeka\HttpClient'));
    }
}
