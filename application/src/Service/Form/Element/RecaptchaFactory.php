<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\Recaptcha;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class RecaptchaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $options = is_array($options) ? $options : [];
        $element = new Recaptcha(null, $options);
        $element->setClient($services->get('Omeka\HttpClient'));

        $settings = $services->get('Omeka\Settings');
        $element->setOptions([
            'site_key' => $settings->get('recaptcha_site_key'),
            'secret_key' => $settings->get('recaptcha_secret_key'),
            'remote_ip' => (new RemoteAddress)->getIpAddress(),
        ]);

        return $element;
    }
}
