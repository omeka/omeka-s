<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Controller plugin for getting a form from the form element manager.
 */
class GetForm extends AbstractPlugin
{
    protected $formElementManager;

    /**
     * Construct the plugin.
     *
     * @param ServiceLocatorInterface $formElementManager
     */
    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    /**
     * Get a form from the form element manager.
     *
     * @param string $class
     * @param array $options
     * @return \Zend\Form\Form
     */
    public function __invoke($class, array $options = null)
    {
        return $this->formElementManager->get($class, $options);
    }
}
