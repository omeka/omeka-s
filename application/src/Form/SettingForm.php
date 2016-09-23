<?php
namespace Omeka\Form;

use DateTimeZone;
use Omeka\Event\Event;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Settings\Settings;
use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;

class SettingForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Settings
     */
    protected $settings;

    public function init()
    {
        $this->add([
            'name' => 'administrator_email',
            'type' => 'Email',
            'options' => [
                'label' => 'Administrator Email', // @translate
            ],
            'attributes' => [
                'value'    => $this->settings->get('administrator_email'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'installation_title',
            'type' => 'Text',
            'options' => [
                'label' => 'Installation Title', // @translate
            ],
            'attributes' => [
                'value'    => $this->settings->get('installation_title'),
                'id' => 'installation-title',
                'required' => true,
            ],
        ]);

        $timeZones = DateTimeZone::listIdentifiers();
        $timeZones = array_combine($timeZones, $timeZones);
        $this->add([
            'name' => 'time_zone',
            'type' => 'Select',
            'options' => [
                'label' => 'Time Zone', // @translate
                'value_options' => $timeZones,
            ],
            'attributes' => [
                'id' => 'time-zone',
                'required' => true,
                'value' => $this->settings->get('time_zone', 'UTC'),
            ],
        ]);

        $this->add([
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => [
                'label' => 'Results per page', // @translate
                'info' => 'The maximum number of results per page on browse pages.', // @translate
            ],
            'attributes' => [
                'value'    => $this->settings->get('pagination_per_page'),
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => [
                'label' => 'Property Label Information', // @translate
                'info' => 'The additional information that accompanies labels on resource pages.', // @translate
                'value_options' =>  [
                    'none' => 'None',
                    'vocab' => 'Show Vocabulary',
                    'term' => 'Show Term'
                ],
            ],
            'attributes' => [
                'value'    => $this->settings->get('property_label_information'),
            ],
        ]);

        $this->add([
            'name' => 'default_site',
            'type' => ResourceSelect::class,
            'options' => [
                'label' => 'Default Site', // @translate
                'info' => 'Select which site should appear when users go to the front page of the installation.', // @translate
                'empty_option' => 'No default (Show index of sites)', // @translate
                'resource_value_options' => [
                    'resource' => 'sites',
                    'query' => [],
                    'option_text_callback' => function ($site) {
                        return $site->title();
                    },
                ],
            ],
            'attributes' => [
                'value'    => $this->settings->get('default_site'),
                'required' => false,
            ],
        ]);

        $this->add([
            'name'    => 'use_htmlpurifier',
            'type'    => 'Checkbox',
            'options' => [
                'label' => 'Use HTMLPurifier', // @translate
                'info'  => 'Clean up user-entered HTML.' // @translate
            ],
            'attributes' => [
                'value'    => $this->settings->get('use_htmlpurifier'),
            ],
        ]);

        $this->add([
            'type' => 'text',
            'name' => 'recaptcha_site_key',
            'options' => [
                'label' => 'reCAPTCHA site key', // @translate
            ],
        ]);
        $this->add([
            'type' => 'text',
            'name' => 'recaptcha_secret_key',
            'options' => [
                'label' => 'reCAPTCHA secret key', // @translate
            ],
        ]);

        $event = new Event('global_settings_form.add_elements', $this, ['form' => $this]);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name' => 'pagination_per_page',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                ['name' => 'Digits']
            ],
        ]);

        $inputFilter->add([
            'name' => 'default_site',
            'allow_empty' => true,
        ]);

        $event = new Event('global_settings_form.add_input_filters', $this, ['form' => $this, 'inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }

    /**
     * @param Settings $settings
     */
    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

}
