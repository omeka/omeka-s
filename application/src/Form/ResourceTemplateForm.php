<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceClassSelect;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;

class ResourceTemplateForm extends Form
{
    use EventManagerAwareTrait;

    public function init()
    {
        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:label',
            ],
        ]);

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourceClassSelect::class,
            'options' => [
                'label' => 'Suggested class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class',
                'id' => 'o:resource_class[o:id]',
            ],
        ]);

        $this->add([
            'name' => 'o:title_property[o:id]',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'title-property-id',
            ],
        ]);
        $this->add([
            'name' => 'o:description_property[o:id]',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'description-property-id',
            ],
        ]);

        $this->add([
            'type' => Fieldset::class,
            'name' => 'o:settings',
            'options' => [
                'label' => 'Other settings', // @translate
            ],
        ]);
        $settingsFieldset = $this->get('o:settings');
        $settingsFieldset
            ->add([
                'name' => 'allowed_languages',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Allowed languages for properties', // @translate
                ],
                'attributes' => [
                    'id' => 'allowed_languages',
                ],
            ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:label',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'allow_empty' => true,
        ]);
        $inputFilter->get('o:settings')
            ->add([
                'name' => 'allowed_languages',
                'required' => false,
                'filters' => [
                    [
                        'name' => \Laminas\Filter\Callback::class,
                        'options' => [
                            'callback' => [$this, 'stringToList'],
                        ],
                    ],
                ],
            ]);

        // Separate events because calling $form->getInputFilters() resets
        // everything.
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }

    public function setData($data)
    {
        if (isset($data['o:settings']['allowed_languages']) && is_array($data['o:settings']['allowed_languages'])) {
            $data['o:settings']['allowed_languages'] = implode("\n", $data['o:settings']['allowed_languages']);
        }
        return parent::setData($data);
    }

    /**
     * Get each line of a string separately.
     *
     * @param string $string
     * @return array
     */
    public function stringToList($string)
    {
        return array_filter(array_map('trim', explode("\n", str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $string))), 'strlen');
    }
}
