<?php
namespace Omeka\Form;

use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractForm extends Form implements ServiceLocatorAwareInterface
{
    /**
     * Form options. Set default options in concrete implementations.
     *
     * @var array
     */
    protected $options = array();

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Construct the object.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param array $options
     */
    public function __construct(ServiceLocatorInterface $serviceLocator,
        array $options = array()
    ) {
        $this->setServiceLocator($serviceLocator);
        parent::__construct($this->getFormName());
        $this->options = array_merge($this->options, $options);
        $this->buildForm();
    }

    /**
     * Get the name of this form.
     *
     * @return null|int|string
     */
    abstract public function getFormName();

    /**
     * Build this form's elements, input filters, etc.
     */
    abstract public function buildForm();

    /**
     * Get an option
     *
     * @param string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

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

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
