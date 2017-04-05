<?php
namespace Omeka\Form;

use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;

class SiteForm extends Form
{
    use EventManagerAwareTrait;

    public function init()
    {
        $this->setAttribute('id', 'site-form');

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'id' => 'title',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => [
                'label' => 'URL slug', // @translate
            ],
            'attributes' => [
                'id' => 'slug',
                'required' => false,
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $triggerResult = $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();

        // Separate events because calling $form->getInputFilters()
        // resets everythhing
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }
}
