<?php
namespace Omeka\Form\Initializer;

use Interop\Container\ContainerInterface;
use Zend\Form\Form;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\Validator\NotEmpty;

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
        $csrfName = $name ? $name . '_csrf' : 'csrf';
        $form->add([
            'type' => 'csrf',
            'name' => $csrfName,
            'options' => [
                'label' => 'CSRF',
                'csrf_options' => [
                    'timeout' => 3600,
                ],
            ],
        ]);
        $form->getInputFilter()->get($csrfName)->getValidatorChain()
            ->prependByName(
                'NotEmpty',
                array(
                    'messages' => array(
                        NotEmpty::IS_EMPTY => "No form data received. Perhaps a file was too large?"
                    ),
                ),
                true
            );
    }
}
