<?php
namespace Omeka\Form;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;
use Laminas\Form\Form;

class BlockLayoutDataForm extends Form
{
    use EventManagerAwareTrait;

    public function init()
    {
        $this->add([
            'name' => 'class',
            'type' => 'text',
            'options' => [
                'label' => 'Class', // @translate
            ],
            'attributes' => [
                'id' => 'block-layout-data-class',
            ],
        ]);
        $this->add([
            'name' => 'alignment',
            'type' => 'select',
            'options' => [
                'label' => 'Alignment', // @translate
                'empty_option' => 'Default', // @translate
                'value_options' => [
                    'left' => 'Float left', // @translate
                    'right' => 'Float right', // @translate
                    'center' => 'Center', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'block-layout-data-alignment',
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }
}
