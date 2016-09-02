<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\Recaptcha;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class RecaptchaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new Recaptcha(null, $options);
        $element->setClient($services->get('Omeka\HttpClient'));
        return $element;
    }
}
