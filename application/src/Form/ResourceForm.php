<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceSelect;
use Zend\Form\Form;
use Zend\View\Helper\Url;

class ResourceForm extends Form
{
    /**
     * @var Url
     */
    protected $urlHelper;

    public function init()
    {
        $urlHelper = $this->getUrlHelper();
        $this->add([
            'name' => 'o:resource_template[o:id]',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'resource-template-select',
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
            ],
            'options' => [
                'label' => 'Resource Template', // @translate
                'info' => 'A pre-defined template for resource creation.', // @translate
                'empty_option' => 'Select Template', // @translate
                'resource_value_options' => [
                    'resource' => 'resource_templates',
                    'query' => [],
                    'option_text_callback' => function ($resourceTemplate) {
                        return $resourceTemplate->label();
                    },
                ],
            ],
        ]);

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'resource-class-select',
            ],
            'options' => [
                'label' => 'Class', // @translate
                'info' => 'A type for the resource. Different types have different default properties attached to them.', // @translate
                'empty_option' => 'Select Class', // @translate
                'resource_value_options' => [
                    'resource' => 'resource_classes',
                    'query' => [],
                    'option_text_callback' => function ($resourceClass) {
                        return [
                            $resourceClass->vocabulary()->label(),
                            $resourceClass->label()
                        ];
                    },
                ],
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:resource_template[o:id]',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'required' => false,
        ]);
    }

    /**
     * @param Url $urlHelper
     */
    public function setUrlHelper(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return Url
     */
    public function getUrlHelper()
    {
        return $this->urlHelper;
    }
}
