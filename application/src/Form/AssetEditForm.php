<?php
namespace Omeka\Form;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;
use Laminas\Form\Form;

class AssetEditForm extends Form
{
    use EventManagerAwareTrait;

    public function init()
    {
        $this->add([
            'name' => 'o:name',
            'type' => 'Text',
            'options' => [
                'label' => 'Name', // @translate
            ],
            'attributes' => [
                'id' => 'asset-name',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:alt_text',
            'type' => 'Textarea',
            'options' => [
                'label' => 'Alt text', // @translate
            ],
            'attributes' => [
                'id' => 'asset-alt-text',
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:name',
            'required' => true,
        ]);

        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }
}
