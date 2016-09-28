<?php
namespace Omeka\Form;

use Omeka\Settings\SiteSettings;
use Omeka\Event\Event;
use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;

class SiteSettingsForm extends Form
{
    use EventManagerAwareTrait;
    
    /**
     * @var SiteSettings
     */
    protected $siteSettings;

    public function init()
    {
        $this->add([
            'name' => 'browse_attached_items',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Restrict browse to attached items', // @translate
            ],
            'attributes' => [
                'value' => (bool) $this->getSiteSettings()->get('browse_attached_items', false),
            ],
        ]);

        $this->add([
            'name' => 'attachment_link_type',
            'type' => 'Select',
            'options' => [
                'label' => 'Attachment link type', // @translate
                'value_options' => [
                    'item' => 'Item page', // @translate
                    'media' => 'Media page', // @translate
                    'original' => 'Direct link to file', // @translate
                ],
            ],
            'attributes' => [
                'value' => $this->getSiteSettings()->get('attachment_link_type'),
            ]
        ]);
        
        $addEvent = new Event(Event::SITE_SETTINGS_ADD_ELEMENTS, $this, ['form' => $this]);
        $this->getEventManager()->triggerEvent($addEvent);

        // Separate events because calling $form->getInputFilters()
        // resets everythhing
        $inputFilter = $this->getInputFilter();
        $filterEvent = new Event(Event::SITE_SETTINGS_ADD_INPUT_FILTERS, $this, ['form' => $this, 'inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($filterEvent);
    }

    /**
     * @param SiteSettings $siteSettings
     */
    public function setSiteSettings(SiteSettings $siteSettings)
    {
        $this->siteSettings = $siteSettings;
    }

    /**
     * @return SiteSettings
     */
    public function getSiteSettings()
    {
        return $this->siteSettings;
    }
}
