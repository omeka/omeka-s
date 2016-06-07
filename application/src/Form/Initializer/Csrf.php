<?php
namespace Omeka\Form\Initializer;

use Zend\Form\Form;
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Csrf implements InitializerInterface
{
    public function initialize($form, ServiceLocatorInterface $serviceLocator)
    {
        if (!$form instanceof Form) {
            return;
        }

        // All forms should have CSRF protection. Must add this before building
        // the form so getInputFilter() knows about it.
        $name = $form->getName();
        $form->add([
            'type' => 'csrf',
            'name' => $name ? $name . '_csrf' : 'csrf',
            'options' => [
                'csrf_options' => [
                    'timeout' => 3600,
                ],
            ],
        ]);
    }
}
