<?php
namespace Omeka\Form\Initializer;

use Interop\Container\ContainerInterface;
use Zend\Form\Form;
use Zend\ServiceManager\Initializer\InitializerInterface;

class Csrf implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $form)
    {
        if (!$form instanceof Form) {
            return;
        }

        // All forms should have CSRF protection. Must add this before building
        // the form so getInputFilter() knows about it.
        $name = $form->getName();
        $csrf_name = $name ? $name . '_csrf' : 'csrf';
        $form->add([
            'type' => 'csrf',
            'name' => $csrf_name,
            'options' => [
                'label' => 'CSRF',
                'csrf_options' => [
                    'timeout' => 3600,
                ],
            ],
        ]);
        $form->get($csrf_name)->getCsrfValidator()->
            ->setMessage("No form data received. Perhaps a file was too large?");
    }
}
