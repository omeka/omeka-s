<?php declare(strict_types=1);

namespace Sparql\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class SparqlForm extends Form
{
    public function init(): void
    {
        $this
            // Don't add the csrf in a search form.
            ->remove('csrf')
            ->setAttribute('id', 'form-sparql')
            // The protocol allows "get" or "post" for standard query.
            ->setAttribute('method', 'GET')
            ->add([
                'name' => 'query',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Query', // @translate
                ],
                'attributes' => [
                    'id' => 'query',
                    'rows' => 10,
                ],
            ])
            ->add([
                'name' => 'prepend_prefixes',
                'type' => CommonElement\OptionalCheckbox::class,
                'options' => [
                    'label' => 'Prepend prefixes', // @translate
                    // Info: the standard prefixes are always included.
                ],
                'attributes' => [
                    'id' => 'prepend_prefixes',
                ],
            ])
            ->add([
                'name' => 'format',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Format', // @translate
                    'value_options' => [
                        'html' => 'Table', // @translate
                        'text' => 'Text', // @translate
                    ],
                    'label_attributes' => [
                        'class' => 'type-radio',
                    ],
                ],
                'attributes' => [
                    'id' => 'format',
                    'value' => 'html',
                ],
            ])
            ->add([
                'name' => 'submit',
                'type' => Element\Submit::class,
                'options' => [
                    'label' => 'Search', // @translate
                ],
                'attributes' => [
                    'id' => 'submit',
                    'form' => 'form-sparql',
                    'value' => 'Search', // @translate
                ],
            ])
        ;
    }
}
