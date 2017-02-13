<?php
namespace Omeka\Form\Element;

use Zend\Form\Element;
use Zend\InputFilter\InputProviderInterface;

/**
 * Textarea element for HTML.
 *
 * Purifies the markup after form submission.
 */
class Asset extends Element implements InputProviderInterface
{
    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => false,
            'validators' => [
                [
                    'name' => 'Regex',
                    'options' => ['pattern' => '/^[0-9]+$/'],
                ],
            ],
        ];
    }
}
