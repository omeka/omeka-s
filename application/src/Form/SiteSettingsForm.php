<?php
namespace Omeka\Form;

use Omeka\Settings\SiteSettings;
use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;

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

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        // Separate events because calling $form->getInputFilters()
        // resets everythhing
        $inputFilter = $this->getInputFilter();
        $filterEvent = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
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
