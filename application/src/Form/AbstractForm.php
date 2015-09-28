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
     * @var HelperPluginManager
     */
    protected $viewHelperManager;

    /**
     * Construct the object.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name Optional name for the form (and CSRF name)
     * @param array $options Optional options for the form
     */
    public function __construct(ServiceLocatorInterface $serviceLocator,
        $name = null, $options = array()
    ) {
        $this->setServiceLocator($serviceLocator);
        parent::__construct($name, array_merge($this->options, $options));

        // All forms should have CSRF protection. Must add this before building
        // the form so getInputFilter() knows about it.
        $this->add(array(
            'type' => 'csrf',
            'name' => $name ? $name . '_csrf' : 'csrf',
            'options' => array(
                'csrf_options' => array(
                    'timeout' => 3600,
                ),
            ),
        ));

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

    /**
     * Get a view helper from the manager.
     *
     * @param string $name
     * @return TranslatorInterface
     */
    protected function getViewHelper($name)
    {
        if (!$this->viewHelperManager instanceof HelperPluginManager) {
            $this->viewHelperManager = $this->getServiceLocator()
                ->get('ViewHelperManager');
        }
        return $this->viewHelperManager->get($name);
    }
}
