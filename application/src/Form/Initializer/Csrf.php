<?php
namespace Omeka\Form\Initializer;

use Interop\Container\ContainerInterface;
use Zend\Form\Form;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\Validator\Csrf as CsrfValidator;

class Csrf implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $form)
    {
        if (!$form instanceof Form) {
            return;
        }

        // All forms should have CSRF protection. Must add this before building
        // the form so getInputFilter() knows about it.
        $name = $form->getName() ? sprintf('%s_csrf', $form->getName()) : 'csrf';
        $form->add([
            'type' => 'csrf',
            'name' => $name,
            'options' => [
                'label' => 'CSRF',
                'csrf_options' => [
                    'timeout' => 3600, // 1 hour
                ],
            ],
        ]);

        // Demystify the default error message: "The form submitted did not
        // originate from the expected site."
        $validator = $form->get($name)->getCsrfValidator();
        $validator->setMessage(
            'Invalid or missing CSRF token', // @translate
            CsrfValidator::NOT_SAME
        );
    }
}
