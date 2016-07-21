<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\Recaptcha;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class RecaptchaFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $formElements)
    {
        $element = new Recaptcha(null, $this->options);
        $element->setClient($formElements->getServiceLocator()->get('Omeka\HttpClient'));
        return $element;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
