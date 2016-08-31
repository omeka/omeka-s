<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Form\Factory\InvokableFactory;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorInterface;

class GetForm extends AbstractPlugin
{
    protected $formElementManager;

    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function __invoke($class, array $options = null)
    {
        // Work around the broken invokable handling for form elements
        if (!$this->formElementManager->has($class)) {
            $this->formElementManager->setFactory($class, new InvokableFactory);
        }
        return $this->formElementManager->get($class, $options);
    }
}
