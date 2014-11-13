<?php
namespace Omeka\Form;

use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractForm extends Form implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Construct the object.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name Optional name for the form
     * @param array $options Optional options for the form
     */
    public function __construct(ServiceLocatorInterface $serviceLocator,
        $name = null, $options = array()
    ) {
        $this->setServiceLocator($serviceLocator);
        parent::__construct($name, array_merge($this->options, $options));
        $this->buildForm();
    }

    /**
     * Build this form's elements, input filters, etc.
     */
    abstract public function buildForm();

    /**
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getServiceLocator()->get('MvcTranslator');
        }
        return $this->translator;
    }
}
