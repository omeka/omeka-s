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
            'type' => 'Text',
            'options' => [
                'label' => 'Class', // @translate
            ],
            'attributes' => [
                'id' => 'block-layout-data-class',
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }
}
