<?php declare(strict_types=1);

namespace Sparql\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Omeka\Form\Element as OmekaElement;

class ConfigForm extends Form
{
    public function init(): void
    {
        $this
            // Indexation.

            ->add([
                'name' => 'sparql_resource_types',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Limit indexation to specific resources', // @translate
                    'value_options' => [
                        'item sets' => 'Item sets', // @translate
                        'items' => 'Items', // @translate
                        'media' => 'Medias', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'sparql_resource_types',
                ],
            ])
            ->add([
                'name' => 'sparql_resource_query',
                'type' => OmekaElement\Query::class,
                'options' => [
                    'label' => 'Limit indexation of items with a query', // @translate
                ],
                'attributes' => [
                    'id' => 'sparql_resource_query',
                ],
            ])
            ->add([
                'name' => 'sparql_resource_private',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Output private resources and values', // @translate
                ],
                'attributes' => [
                    'id' => 'sparql_resource_private',
                ],
            ])
            ->add([
                'name' => 'sparql_fields_included',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Omeka metadata to include', // @translate
                    'value_options' => [
                        'o:owner' => 'Owner',
                        'o:is_public' => 'Visibility', // @translate
                        // The class is automatically included as type of the
                        // resource according to json-ld representation.
                        'o:resource_class' => 'Resource class', // @translate
                        'o:resource_template' => 'Resource template', // @translate
                        'o:thumbnail' => 'Thumbnail', // @translate
                        'o:title' => 'Title', // @translate
                        'rdfs:label' => 'Title as rdf label', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'sparql_fields_included',
                ],
            ])
            ->add([
                'name' => 'sparql_property_whitelist',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
                    'label' => 'Limit indexation to specific properties', // @translate
                    'term_as_value' => true,
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'sparql_property_whitelist',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select properties…', // @translate
                ],
            ])
            ->add([
                'name' => 'sparql_property_blacklist',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
                    'label' => 'Skip indexation for specific properties', // @translate
                    'term_as_value' => true,
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'sparql_property_blacklist',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select properties…', // @translate
                ],
            ])
            ->add([
                'name' => 'sparql_datatype_whitelist',
                'type' => CommonElement\DataTypeSelect::class,
                'options' => [
                    'label' => 'Limit indexation to specific data types', // @translate
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'sparql_datatype_whitelist',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select data types…', // @translate
                ],
            ])
            ->add([
                'name' => 'sparql_datatype_blacklist',
                'type' => CommonElement\DataTypeSelect::class,
                'options' => [
                    'label' => 'Skip indexation for specific data types', // @translate
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'sparql_datatype_blacklist',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select data types…', // @translate
                ],
            ])

            ->add([
                'name' => 'sparql_arc2_write_key',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Write access key for Arc2 (random)', // @translate
                ],
                // This value is stored in table triplestore_setting, managed by arc2.
                'attributes' => [
                    'id' => 'sparql_arc2_write_key',
                    'readonly' => 'readonly',
                    'value' => substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(128))), 0, 24),
                ],
            ])

            ->add([
                'name' => 'sparql_fuseki_endpoint',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Fuseki endpoint', // @translate
                    'info',
                ],
                'attributes' => [
                    'id' => 'sparql_fuseki_endpoint',
                ],
            ])
            ->add([
                'name' => 'sparql_fuseki_authmode',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Fuseki authentication mode', // @translate
                    'value_options' => [
                        '' => 'None', // @translate
                        'basic' => 'Basic', // @translate
                        'digest' => 'Digest', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'sparql_fuseki_authmode',
                ],
            ])
            ->add([
                'name' => 'sparql_fuseki_username',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Fuseki username', // @translate
                ],
                'attributes' => [
                    'id' => 'sparql_fuseki_username',
                ],
            ])
            ->add([
                'name' => 'sparql_fuseki_password',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Fuseki password', // @translate
                ],
                'attributes' => [
                    'id' => 'sparql_fuseki_password',
                ],
            ])

            ->add([
                'name' => 'sparql_indexes',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Index in sparql engine', // @translate
                    'value_options' => [
                        'db' => 'Internal database (used for the internal sparql endpoint)', // @translate
                        'fuseki' => 'Fuseki (by resource)', // @translate
                        'fuseki_file' => 'Fuseki (in bulk via file)', // @translate
                        'turtle' => 'Triplestore (turtle file, used to index in bulk any third party sparql server)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'sparql_indexes',
                ],
            ])

            ->add([
                'name' => 'process',
                'type' => Element\Submit::class,
                'options' => [
                    'label' => 'Index', // @translate
                ],
                'attributes' => [
                    'id' => 'process',
                    'value' => 'Process', // @translate
                ],
            ])

            // Search.

            ->add([
                'name' => 'sparql_endpoint',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Endpoint', // @translate
                    'info' => 'When using yasgui, clear the cache of the browser after modifying these options.', // @translate
                    'value_options' => [
                        'auto' => 'Automatic (external if any, else internal if any)', // @translate
                        'none' => 'None', // @translate
                        'internal' => 'Internal (/sparql)', // @translate
                        'external' => 'External (set below)', // @translate
                        'any' => 'Internal and external (set below)', // @translate
                        // TODO Proxify external endpoint with the internal url, in particular for stats.
                    ],
                ],
                'attributes' => [
                    'id' => 'sparql_endpoint',
                ],
            ])
            ->add([
                'name' => 'sparql_endpoint_external',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'External endpoint', // @translate
                    'info' => 'When Fuseki is installed locally, the url to index may be "http://localhost/sparql" and the external endpoint may be "http://example.org/sparql/triplestore".', // @translate
                ],
                'attributes' => [
                    'id' => 'sparql_endpoint_external',
                ],
            ])
            ->add([
                'name' => 'sparql_limit_per_page',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Max results per page (internal endpoint)', // @translate
                ],
                'attributes' => [
                    'id' => 'sparql_limit_per_page',
                ],
            ])
        ;

        $inputFilter = $this->getInputFilter();
        $inputFilter
            ->add([
                'name' => 'sparql_resource_types',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_fields_included',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_property_whitelist',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_property_blacklist',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_datatype_whitelist',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_datatype_blacklist',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_fuseki_authmode',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_indexes',
                'required' => false,
            ])
            ->add([
                'name' => 'sparql_endpoint',
                'required' => false,
            ])
        ;
    }
}
