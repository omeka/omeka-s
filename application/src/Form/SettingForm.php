<?php
namespace Omeka\Form;

use DateTimeZone;
use Omeka\Form\Element\ArrayTextarea;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\RestoreTextarea;
use Omeka\Form\Element\SiteSelect;
use Omeka\Settings\Settings;
use Laminas\Form\Form;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

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
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
        'image/jp2',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/tiff',
        'image/webp',
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
        'tiff', 'txt', 'wav', 'wax', 'webm', 'webp', 'wma', 'wmv', 'wmx', 'wri', 'xla',
        'xls', 'xlsx', 'xlt', 'xlw', 'zip',
    ];

    /**
     * @var Settings
     */
    protected $settings;

    public function init()
    {
        $this->setOption('element_groups', [
            'general' => 'General', // @translate
            'display' => 'Display', // @translate
            'editing' => 'Editing', // @translate
            'search' => 'Search', // @translate
            'security' => 'Security', // @translate
        ]);

        // General element group

        $this->add([
            'name' => 'administrator_email',
            'type' => 'Email',
            'options' => [
                'element_group' => 'general',
                'label' => 'Administrator email', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('administrator_email'),
                'required' => true,
                'id' => 'administrator_email',
            ],
        ]);

        $this->add([
            'name' => 'installation_title',
            'type' => 'Text',
            'options' => [
                'element_group' => 'general',
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
        $this->add([
            'name' => 'time_zone',
            'type' => 'Select',
            'options' => [
                'element_group' => 'general',
                'label' => 'Time zone', // @translate
                'value_options' => $timeZones,
            ],
            'attributes' => [
                'id' => 'time-zone',
                'required' => true,
                'value' => $this->settings->get('time_zone', 'UTC'),
            ],
        ]);

        $this->add([
            'name' => 'locale',
            'type' => 'Omeka\Form\Element\LocaleSelect',
            'options' => [
                'element_group' => 'general',
                'label' => 'Locale', // @translate
                'info' => 'Global locale/language code for all interfaces.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('locale'),
                'class' => 'chosen-select',
                'id' => 'locale',
            ],
        ]);

        $this->add([
            'name' => 'version_notifications',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'general',
                'label' => 'Enable version notifications', // @translate
                'info' => 'Enable notifications when a new version of Omeka S, modules, or themes are available.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('version_notifications'),
                'id' => 'version_notifications',
            ],
        ]);

        $this->add([
            'type' => 'checkbox',
            'name' => 'disable_jsonld_reverse',
            'options' => [
                'element_group' => 'general',
                'label' => 'Disable JSON-LD @reverse', // @translate
                'info' => 'Disable JSON-LD reverse properties in the API output for resources.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('disable_jsonld_reverse'),
                'id' => 'disable-jsonld-reverse',
            ],
        ]);

        $this->add([
            'name' => 'favicon',
            'type' => 'Omeka\Form\Element\Asset',
            'options' => [
                'element_group' => 'general',
                'label' => 'Favicon', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('favicon'),
                'id' => 'favicon',
            ],
        ]);

        // Display element group

        $this->add([
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => [
                'element_group' => 'display',
                'label' => 'Results per page', // @translate
                'info' => 'The maximum number of results per page on browse pages.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('pagination_per_page'),
                'required' => true,
                'id' => 'pagination_per_page',
            ],
        ]);

        $this->add([
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => [
                'element_group' => 'display',
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

        $this->add([
            'name' => 'default_site',
            'type' => SiteSelect::class,
            'options' => [
                'element_group' => 'display',
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

        $this->add([
            'name' => 'disable_jsonld_embed',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'display',
                'label' => 'Disable JSON-LD embed', // @translate
                'info' => 'By default, Omeka embeds JSON-LD in resource browse and show pages for the purpose of machine-readable metadata discovery. Check this to disable embedding.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('disable_jsonld_embed'),
                'id' => 'disable_jsonld_embed',
            ],
        ]);

        // Editing element group

        $this->add([
            'name' => 'default_to_private',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'editing',
              'label' => 'Default content visibility to Private', // @translate
              'info' => 'If checked, all items, item sets and sites newly created will have their visibility set to private by default.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('default_to_private'),
                'id' => 'default_to_private',
            ],
        ]);

        $this->add([
            'name' => 'value_languages',
            'type' => ArrayTextarea::class,
            'options' => [
                'element_group' => 'editing',
                'label' => 'Suggested languages for values', // @translate
                'info' => 'List of languages to facilitate filling of the values in the resource form. List them one by line. The label displayed for a language may be appended with a "=".', // @translate
                'as_key_value' => true,
            ],
            'attributes' => [
                'value' => $this->settings->get('value_languages', []),
                'id' => 'value_languages',
            ],
        ]);

        $this->add([
            'name' => 'media_alt_text_property',
            'type' => PropertySelect::class,
            'options' => [
                'element_group' => 'editing',
                'label' => 'Media alt text property', // @translate
                'info' => 'Media property to use as alt text if no alt text is explicitly set.', // @translate
                'empty_option' => '[None]', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'media_alt_text_property',
                'class' => 'chosen-select',
                'value' => $this->settings->get('media_alt_text_property'),
            ],
        ]);

        // Search element group

        $this->add([
            'name' => 'index_fulltext_search',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'search',
              'label' => 'Index full-text search', // @translate
            ],
            'attributes' => [
                'value' => '',
                'id' => 'index_fulltext_search',
            ],
        ]);

        // Security element group

        $this->add([
            'name' => 'use_htmlpurifier',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'security',
                'label' => 'Use HTMLPurifier', // @translate
                'info' => 'Clean up user-entered HTML.', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('use_htmlpurifier'),
                'id' => 'use_htmlpurifier',
            ],
        ]);

        $this->add([
            'type' => 'checkbox',
            'name' => 'disable_file_validation',
            'options' => [
                'element_group' => 'security',
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
            ->setOption('element_group', 'security')
            ->setAttributes([
                'rows' => '4',
                'id' => 'media_type_whitelist',
            ])
            ->setRestoreButtonText('Restore default media types')
            ->setValue(implode(',', $this->settings->get('media_type_whitelist', [])))
            ->setRestoreValue(implode(',', self::MEDIA_TYPE_WHITELIST));
        $this->add($mediaTypeWhitelist);

        $extensionWhitelist = new RestoreTextarea('extension_whitelist');
        $extensionWhitelist
            ->setLabel('Allowed file extensions') // @translate
            ->setOption('info', 'A comma-separated list of allowed file extensions for file uploads.') // @translate
            ->setOption('element_group', 'security')
            ->setAttributes([
                'rows' => '4',
                'id' => 'extension_whitelist',
            ])
            ->setRestoreButtonText('Restore default extensions')
            ->setValue(implode(',', $this->settings->get('extension_whitelist', [])))
            ->setRestoreValue(implode(',', self::EXTENSION_WHITELIST));
        $this->add($extensionWhitelist);

        $this->add([
            'type' => 'text',
            'name' => 'recaptcha_site_key',
            'options' => [
                'element_group' => 'security',
                'label' => 'reCAPTCHA site key', // @translate
            ],
            'attributes' => [
                'value' => $this->settings->get('recaptcha_site_key'),
                'id' => 'recaptcha_site_key',
            ],
        ]);
        $this->add([
            'type' => 'text',
            'name' => 'recaptcha_secret_key',
            'options' => [
                'element_group' => 'security',
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

        $inputFilter->add([
            'name' => 'pagination_per_page',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                ['name' => 'Digits'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'default_site',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'locale',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'media_alt_text_property',
            'allow_empty' => true,
        ]);

        $inputFilter->add([
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
        $inputFilter->add([
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
