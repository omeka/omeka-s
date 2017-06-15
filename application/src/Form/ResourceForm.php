<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\ResourceClassSelect;
use Zend\Form\Form;
use Zend\View\Helper\Url;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;

class ResourceForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Url
     */
    protected $urlHelper;

    public function init()
    {
        $urlHelper = $this->getUrlHelper();
        $this->setAttribute('class', 'resource-form');
        $this->add([
            'name' => 'o:resource_template[o:id]',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'resource-template-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a template', // @translate
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
            ],
            'options' => [
                'label' => 'Resource template', // @translate
                'info' => 'A pre-defined template for resource creation.', // @translate
                'empty_option' => '',
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
            'type' => ResourceClassSelect::class,
            'attributes' => [
                'id' => 'resource-class-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class', // @translate
            ],
            'options' => [
                'label' => 'Class', // @translate
                'info' => 'A type for the resource. Different types have different default properties attached to them.', // @translate
                'empty_option' => '',
            ],
        ]);

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:resource_template[o:id]',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'required' => false,
        ]);

        $filterEvent = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($filterEvent);
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
