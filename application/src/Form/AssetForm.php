<?php
namespace Omeka\Form;

use Omeka\Form\Element\UserSelect; // fontion qui fiat l enumeration des users mais pas d 'empty option;
use Laminas\Form\Form;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

class AssetForm extends Form implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    // au lieu de 'text'; use Laminas\Form\Element\Text; 'type' => Tex  ::class,

    public function init()
    {
        $this->add([
            'name' => 'fulltext_search', // -> renommer en 'search'
            'type' => 'Text',
            'options' => [
                'label' => 'Search by name', // @translate
            ],
            'attributes' => [
                'id' => 'fulltext_search',
            ],
        ]);

        $this->add([
            'name' => 'owner_id',
            'type' => UserSelect::class,
            'options' => [
                'label' => 'Search by owner', // @translate
                'info' => 'Searches for assets that are owned by this user.', // @translate
                'empty_option' => 'Select user...', // @translate
            ],
            'attributes' => [
                'id' => 'owner_id',
            ],
        ]);

        // est ce vrmt utile ?
        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);
        // est ce vrmt utile ?
        $inputFilter = $this->getInputFilter();
        $inputFilter->add(['name' => 'fulltext_search', 'required' => false]);
        $inputFilter->add(['name' => 'owner_id', 'required' => false]);

        // $this->getEventManager()->triggerEvent(new Event('form.add_elements', $this));
    }
}
