<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\Captcha;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class CaptchaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $element = new Captcha(null, $options ?? []);
        $element->setCaptcha($services->get('Omeka\CaptchaManager')->getActive());
        $element->setRemoteIp((new RemoteAddress)->getIpAddress());
        return $element;
    }
}
