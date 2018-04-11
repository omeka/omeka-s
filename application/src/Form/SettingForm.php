<?php
namespace Omeka\Form;

use DateTimeZone;
use Omeka\Form\Element\SiteSelect;
use Omeka\Form\Element\RestoreTextarea;
use Omeka\Settings\Settings;
use Zend\Form\Form;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;

class SettingForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * The default file media type whitelist.
     */
    const MEDIA_TYPE_WHITELIST = [
        // application/*
        'application/msword',
        'application/ogg',
        'application/pdf',
        'application/rtf',
        'application/vnd.ms-access',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        'application/vnd.ms-project',
        'application/vnd.ms-write',
        'application/vnd.oasis.opendocument.chart',
        'application/vnd.oasis.opendocument.database',
        'application/vnd.oasis.opendocument.formula',
        'application/vnd.oasis.opendocument.graphics',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.text',
        'application/x-gzip',
        'application/x-ms-wmp',
        'application/x-msdownload',
        'application/x-shockwave-flash',
        'application/x-tar',
        'application/zip',
        // audio/*
        'audio/midi',
        'audio/mp4',
        'audio/mpeg',
        'audio/ogg',
        'audio/x-aac',
        'audio/x-aiff',
        'audio/x-ms-wma',
        'audio/x-ms-wax',
        'audio/x-realaudio',
        'audio/x-wav',
        // image/*
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/tiff',
        'image/x-icon',
        // text/*
        'text/css',
        'text/plain',
        'text/richtext',
        // video/*
        'video/divx',
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/quicktime',
        'video/webm',
        'video/x-ms-asf,',
        'video/x-msvideo',
        'video/x-ms-wmv',
    ];

    /**
     * The default file extension whitelist.
     */
    const EXTENSION_WHITELIST = [
        'aac', 'aif', 'aiff', 'asf', 'asx', 'avi', 'bmp', 'c', 'cc', 'class',
        'css', 'divx', 'doc', 'docx', 'exe', 'gif', 'gz', 'gzip', 'h', 'ico',
        'j2k', 'jp2', 'jpe', 'jpeg', 'jpg', 'm4a', 'm4v', 'mdb', 'mid', 'midi', 'mov',
        'mp2', 'mp3', 'mp4', 'mpa', 'mpe', 'mpeg', 'mpg', 'mpp', 'odb', 'odc',
        'odf', 'odg', 'odp', 'ods', 'odt', 'ogg', 'opus', 'pdf', 'png', 'pot', 'pps',
        'ppt', 'pptx', 'qt', 'ra', 'ram', 'rtf', 'rtx', 'swf', 'tar', 'tif',
        'tiff', 'txt', 'wav', 'wax', 'webm', 'wma', 'wmv', 'wmx', 'wri', 'xla', 'xls',
        'xlsx', 'xlt', 'xlw', 'zip',
    ];

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
                'label' => 'Administrator email', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('administrator_email'),
                'required' => true,
                'id' => 'administrator_email',
            ],
        ]);

        $generalFieldset->add([
            'name' => 'installation_title',
            'type' => 'Text',
            'options' => [
                'label' => 'Installation title', // @translate
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
                'label' => 'Time zone', // @translate
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
                'id' => 'pagination_per_page',
            ],
        ]);

        $generalFieldset->add([
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => [
                'label' => 'Property label information', // @translate
                'info' => 'The additional information that accompanies labels on resource pages.', // @translate
                'value_options' => [
                    'none' => 'None', // @translate
                    'vocab' => 'Show Vocabulary', // @translate
                    'term' => 'Show Term', // @translate
                ],
            ],
            'attributes' => [
                'value' => $this->settings->get('property_label_information'),
                'id' => 'property_label_information',
            ],
        ]);

        $generalFieldset->add([
            'name' => 'default_site',
            'type' => SiteSelect::class,
            'options' => [
                'label' => 'Default site', // @translate
                'info' => 'Select which site should appear when users go to the front page of the installation.', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'No default (show index of sites)', // @translate
                'value' => $this->settings->get('default_site'),
                'required' => false,
                'id' => 'default_site',
            ],
        ]);

        $generalFieldset->add([
            'name' => 'locale',
            'type' => 'Omeka\Form\Element\LocaleSelect',
            'options' => [
                'label' => 'Locale', // @translate
                'info' => 'Global locale/language code for all interfaces.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('locale'),
                'class' => 'chosen-select',
                'id' => 'locale',
            ],
        ]);

        $generalFieldset->add([
            'name' => 'version_notifications',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Enable version notifications', // @translate
                'info' => 'Enable notifications when a new version of Omeka S, modules, or themes are available.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('version_notifications'),
                'id' => 'version_notifications',
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
                'id' => 'use_htmlpurifier',
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
                'id' => 'disable_file_validation',
            ],
        ]);
        $mediaTypeWhitelist = new RestoreTextarea('media_type_whitelist');
        $mediaTypeWhitelist
            ->setLabel('Allowed media types') // @translate
            ->setOption('info', 'A comma-separated list of allowed media types for file uploads.') // @translate
            ->setAttributes([
                'rows' => '4',
                'id' => 'media_type_whitelist',
            ])
            ->setRestoreButtonText('Restore default media types')
            ->setValue(implode(',', $this->settings->get('media_type_whitelist', [])))
            ->setRestoreValue(implode(',', self::MEDIA_TYPE_WHITELIST));
        $securityFieldset->add($mediaTypeWhitelist);

        $extensionWhitelist = new RestoreTextarea('extension_whitelist');
        $extensionWhitelist
            ->setLabel('Allowed file extensions') // @translate
            ->setOption('info', 'A comma-separated list of allowed file extensions for file uploads.') // @translate
            ->setAttributes([
                'rows' => '4',
                'id' => 'extension_whitelist',
            ])
            ->setRestoreButtonText('Restore default extensions')
            ->setValue(implode(',', $this->settings->get('extension_whitelist', [])))
            ->setRestoreValue(implode(',', self::EXTENSION_WHITELIST));
        $securityFieldset->add($extensionWhitelist);

        $securityFieldset->add([
            'type' => 'text',
            'name' => 'recaptcha_site_key',
            'options' => [
                'label' => 'reCAPTCHA site key', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('recaptcha_site_key'),
                'id' => 'recaptcha_site_key',
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
                'id' => 'recaptcha_secret_key',
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
        $generalInputFilter->add([
            'name' => 'locale',
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
