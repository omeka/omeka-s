<?php
namespace Omeka\Form;

use DateTimeZone;
use Omeka\File\Manager as FileManager;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\RestoreTextarea;
use Omeka\Settings\Settings;
use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;

class SettingForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Settings
     */
    protected $settings;

    public function init()
    {
        // General fieldset

        $this->add([
            'type' => 'fieldset',
            'name' => 'general',
            'options' => [
                'label' => 'General', // @translate
            ],
        ]);
        $generalFieldset = $this->get('general');

        $generalFieldset->add([
            'name' => 'administrator_email',
            'type' => 'Email',
            'options' => [
                'label' => 'Administrator Email', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('administrator_email'),
                'required' => true,
            ],
        ]);

        $generalFieldset->add([
            'name' => 'installation_title',
            'type' => 'Text',
            'options' => [
                'label' => 'Installation Title', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('installation_title'),
                'id' => 'installation-title',
                'required' => true,
            ],
        ]);

        $timeZones = DateTimeZone::listIdentifiers();
        $timeZones = array_combine($timeZones, $timeZones);
        $generalFieldset->add([
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

        $generalFieldset->add([
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => [
                'label' => 'Results per page', // @translate
                'info' => 'The maximum number of results per page on browse pages.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('pagination_per_page'),
                'required' => true,
            ],
        ]);

        $generalFieldset->add([
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => [
                'label' => 'Property Label Information', // @translate
                'info' => 'The additional information that accompanies labels on resource pages.', // @translate
                'value_options' => [
                    'none' => 'None',
                    'vocab' => 'Show Vocabulary',
                    'term' => 'Show Term',
                ],
            ],
            'attributes' => [
                'value' => $this->settings->get('property_label_information'),
            ],
        ]);

        $generalFieldset->add([
            'name' => 'default_site',
            'type' => ResourceSelect::class,
            'options' => [
                'label' => 'Default Site', // @translate
                'info' => 'Select which site should appear when users go to the front page of the installation.', // @translate
                'empty_option' => '',
                'resource_value_options' => [
                    'resource' => 'sites',
                    'query' => [],
                    'option_text_callback' => function ($site) {
                        return $site->title();
                    },
                ],
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'No default (show index of sites)', // @translate
                'value' => $this->settings->get('default_site'),
                'required' => false,
            ],
        ]);

        // Security fieldset

        $this->add([
            'type' => 'fieldset',
            'name' => 'security',
            'options' => [
                'label' => 'Security', // @translate
            ],
        ]);
        $securityFieldset = $this->get('security');

        $securityFieldset->add([
            'name' => 'use_htmlpurifier',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Use HTMLPurifier', // @translate
                'info' => 'Clean up user-entered HTML.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('use_htmlpurifier'),
            ],
        ]);

        $securityFieldset->add([
            'type' => 'checkbox',
            'name' => 'disable_file_validation',
            'options' => [
                'label' => 'Disable file validation', // @translate
                'info' => 'Check this to disable file media type and extension validation.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('disable_file_validation'),
            ],
        ]);
        $mediaTypeWhitelist = new RestoreTextarea('media_type_whitelist');
        $mediaTypeWhitelist
            ->setLabel('Allowed media types') // @translate
            ->setOption('info', 'A comma-separated list of allowed media types for file uploads.') // @translate
            ->setAttribute('rows', '4')
            ->setRestoreButtonText('Restore default media types')
            ->setValue(implode(',', $this->settings->get('media_type_whitelist', [])))
            ->setRestoreValue(implode(',', FileManager::MEDIA_TYPE_WHITELIST));
        $securityFieldset->add($mediaTypeWhitelist);

        $extensionWhitelist = new RestoreTextarea('extension_whitelist');
        $extensionWhitelist
            ->setLabel('Allowed file extensions') // @translate
            ->setOption('info', 'A comma-separated list of allowed file extensions for file uploads.') // @translate
            ->setAttribute('rows', '4')
            ->setRestoreButtonText('Restore default extensions')
            ->setValue(implode(',', $this->settings->get('extension_whitelist', [])))
            ->setRestoreValue(implode(',', FileManager::EXTENSION_WHITELIST));
        $securityFieldset->add($extensionWhitelist);

        $securityFieldset->add([
            'type' => 'text',
            'name' => 'recaptcha_site_key',
            'options' => [
                'label' => 'reCAPTCHA site key', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('recaptcha_site_key'),
            ],
        ]);
        $securityFieldset->add([
            'type' => 'text',
            'name' => 'recaptcha_secret_key',
            'options' => [
                'label' => 'reCAPTCHA secret key', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('recaptcha_secret_key'),
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $triggerResult = $this->getEventManager()->triggerEvent($event);

        // Input filters

        $inputFilter = $this->getInputFilter();

        $generalInputFilter = $inputFilter->get('general');
        $generalInputFilter->add([
            'name' => 'pagination_per_page',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                ['name' => 'Digits'],
            ],
        ]);
        $generalInputFilter->add([
            'name' => 'default_site',
            'allow_empty' => true,
        ]);

        $securityInputFilter = $inputFilter->get('security');
        $securityInputFilter->add([
            'name' => 'media_type_whitelist',
            'required' => false,
            'filters' => [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => function ($mediaTypes) {
                            $mediaTypes = explode(',', $mediaTypes);
                            $mediaTypes = array_map('trim', $mediaTypes); // trim all
                            $mediaTypes = array_filter($mediaTypes); // remove empty
                            $mediaTypes = array_unique($mediaTypes); // remove duplicate
                            return $mediaTypes;
                        },
                    ],
                ],
            ],
        ]);
        $securityInputFilter->add([
            'name' => 'extension_whitelist',
            'required' => false,
            'filters' => [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => function ($extensions) {
                            $extensions = explode(',', $extensions);
                            $extensions = array_map('trim', $extensions); // trim all
                            $extensions = array_filter($extensions); // remove empty
                            $extensions = array_unique($extensions); // remove duplicate
                            return $extensions;
                        },
                    ],
                ],
            ],
        ]);

        // Separate events because calling getInputFilters() resets everything.
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
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
