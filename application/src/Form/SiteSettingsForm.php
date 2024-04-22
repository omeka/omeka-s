<?php
namespace Omeka\Form;

use Omeka\Form\Element\BrowseDefaults;
use Omeka\Form\Element\PropertySelect;
use Omeka\Settings\SiteSettings;
use Omeka\Stdlib\Browse as BrowseService;
use Laminas\Form\Form;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

class SiteSettingsForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var SiteSettings
     */
    protected $siteSettings;

    /**
     * @var BrowseService
     */
    protected $browseService;

    public function init()
    {
        $settings = $this->getSiteSettings();

        $this->setOption('element_groups', [
            'general' => 'General', // @translate
            'language' => 'Language', // @translate
            'browse' => 'Browse', // @translate
            'show' => 'Show', // @translate
            'search' => 'Search', // @translate
        ]);

        // o:assign_new_items element is a pseudo-setting that's ultimately set
        // as a property of the site and not as a site setting.
        $this->add([
            'name' => 'o:assign_new_items',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'general',
                'label' => 'Auto-assign new items', // @translate
                'info' => 'Select this if you want new items to be automatically assigned to this site. Note that item owners may unassign their items at any time.', // @translate
            ],
            'attributes' => [
                'id' => 'assign_new_items',
                'value' => true,
            ],
        ]);
        $this->add([
            'name' => 'attachment_link_type',
            'type' => 'Select',
            'options' => [
                'element_group' => 'general',
                'label' => 'Attachment link type', // @translate
                'value_options' => [
                    'item' => 'Item page', // @translate
                    'media' => 'Media page', // @translate
                    'original' => 'Direct link to file', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'attachment_link_type',
                'value' => $settings->get('attachment_link_type'),
            ],
        ]);
        $this->add([
            'name' => 'show_page_pagination',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'general',
                'label' => 'Show page pagination', // @translate
                'info' => 'Show pagination that helps users follow a linear narrative through a site.', // @translate
            ],
            'attributes' => [
                'id' => 'show_page_pagination',
                'value' => $settings->get('show_page_pagination', true),
            ],
        ]);
        $this->add([
            'name' => 'show_user_bar',
            'type' => 'radio',
            'options' => [
                'element_group' => 'general',
                'label' => 'Show user bar on public views', // @translate
                'value_options' => [
                    '-1' => 'Never', // @translate
                    '0' => 'When logged in', // @translate
                    '1' => 'Always', // @translate
                ],
            ],
            'attributes' => [
                'value' => $settings->get('show_user_bar', '0'),
            ],
        ]);
        $this->add([
            'name' => 'disable_jsonld_embed',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'general',
                'label' => 'Disable JSON-LD embed', // @translate
                'info' => 'By default, Omeka embeds JSON-LD in resource browse and show pages for the purpose of machine-readable metadata discovery. Check this to disable embedding.', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('disable_jsonld_embed'),
                'id' => 'disable_jsonld_embed',
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
                'value' => $settings->get('favicon'),
                'id' => 'favicon',
            ],
        ]);
        $this->add([
            'name' => 'subnav_display',
            'type' => 'select',
            'options' => [
                'element_group' => 'general',
                'label' => 'Page subnavigation display', // @translate
                'empty_option' => 'Hide on leaf pages (default)', // @translate
                'value_options' => [
                    'hide' => 'Hide on all pages', // @translate
                    'show' => 'Show on all pages', // @translate
                ],
            ],
            'attributes' => [
                'value' => $settings->get('subnav_display'),
                'id' => 'disable_jsonld_embed',
            ],
        ]);

        // Language section
        $this->add([
            'name' => 'locale',
            'id' => 'locale',
            'type' => 'Omeka\Form\Element\LocaleSelect',
            'options' => [
                'element_group' => 'language',
                'label' => 'Locale', // @translate
                'info' => 'Locale/language code for this site. Leave blank to use the global locale setting.', // @translate
            ],
            'attributes' => [
                'id' => 'locale',
                'value' => $settings->get('locale'),
                'class' => 'chosen-select',
            ],
        ]);

        $this->add([
            'name' => 'filter_locale_values',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'language',
                'label' => 'Filter values based on site locale', // @translate
                'info' => 'Show only values matching the site language setting and values without locale ID.', // @translate
            ],
            'attributes' => [
                'id' => 'filter_locale_values',
                'value' => (bool) $settings->get('filter_locale_values', false),
            ],
        ]);

        $this->add([
            'name' => 'show_locale_label',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'language',
                'label' => 'Show language labels for values', // @translate
                'info' => 'Show a label indicating the language of each value on show pages.', // @translate
            ],
            'attributes' => [
                'id' => 'show_locale_label',
                'value' => (bool) $settings->get('show_locale_label', true),
            ],
        ]);

        // Browse section
        $this->add([
            'name' => 'browse_attached_items',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'browse',
                'label' => 'Restrict browse to attached items', // @translate
            ],
            'attributes' => [
                'id' => 'browse_attached_items',
                'value' => (bool) $settings->get('browse_attached_items', false),
            ],
        ]);
        $this->add([
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => [
                'element_group' => 'browse',
                'label' => 'Results per page', // @translate
                'info' => 'The maximum number of results per page on browse pages. Leave blank to use the global setting.', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('pagination_per_page'),
                'required' => false,
                'id' => 'pagination_per_page',
                'placeholder' => 'Use global setting', // @translate
            ],
        ]);
        $headingTerm = $settings->get('browse_heading_property_term');
        $this->add([
            'name' => 'browse_heading_property_term',
            'type' => PropertySelect::class,
            'options' => [
                'element_group' => 'browse',
                'label' => 'Browse heading property', // @translate
                'info' => 'Use this property for the heading of each resource on a browse page. Keep unselected to use the default title property of each resource.', // @translate
                'term_as_value' => true,
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'browse_heading_property_term',
                'value' => $headingTerm,
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);
        $bodyTerm = $settings->get('browse_body_property_term');
        $this->add([
            'name' => 'browse_body_property_term',
            'type' => PropertySelect::class,
            'options' => [
                'element_group' => 'browse',
                'label' => 'Browse body property', // @translate
                'info' => 'Use this property for the body of each resource on a browse page. Keep unselected to use the default description property of each resource.', // @translate
                'term_as_value' => true,
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'browse_body_property_term',
                'value' => $bodyTerm,
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);
        $this->add([
            'name' => 'browse_defaults_public_items',
            'type' => BrowseDefaults::class,
            'options' => [
                'element_group' => 'browse',
                'label' => 'Item browse defaults', // @translate
                'browse_defaults_context' => 'public',
                'browse_defaults_resource_type' => 'items',
            ],
            'attributes' => [
                'value' => json_encode($this->browseService->getBrowseConfig('public', 'items')),
            ],
        ]);

        // Show section
        $this->add([
            'name' => 'show_attached_pages',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'show',
                'label' => 'Show attached pages', // @translate
                'info' => 'Show site pages to which an item is attached on the public item show page.', // @translate
            ],
            'attributes' => [
                'id' => 'show_attached_pages',
                'value' => (bool) $settings->get('show_attached_pages', true),
            ],
        ]);
        $this->add([
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => [
                'element_group' => 'show',
                'label' => 'Property label information', // @translate
                'info' => 'The additional information that accompanies labels on resource pages.', // @translate
                'value_options' => [
                    'none' => 'None', // @translate
                    'vocab' => 'Show Vocabulary', // @translate
                    'term' => 'Show Term', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'property_label_information',
                'value' => $settings->get('property_label_information', 'none'),
            ],
        ]);
        $this->add([
            'name' => 'show_value_annotations',
            'type' => 'select',
            'options' => [
                'element_group' => 'show',
                'label' => 'Value annotations', // @translate
                'empty_option' => 'Hide value annotations', // @translate
                'value_options' => [
                    'collapsed' => 'Show value annotations (collapsed)', // @translate
                    'expanded' => 'Show value annotations (expanded)', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'show_value_annotations',
                'value' => $settings->get('show_value_annotations'),
            ],
        ]);
        $this->add([
            'name' => 'exclude_resources_not_in_site',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'show',
                'label' => 'Exclude resources not in site', // @translate
                'info' => 'Exclude resources that are not assigned to this site.', // @translate
            ],
            'attributes' => [
                'id' => 'exclude_resources_not_in_site',
                'value' => (bool) $settings->get('exclude_resources_not_in_site', false),
            ],
        ]);
        $this->add([
            'name' => 'item_media_embed',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'show',
                'label' => 'Embed media on item pages (legacy)', // @translate
            ],
            'attributes' => [
                'id' => 'item_media_embed',
                'value' => (bool) $settings->get('item_media_embed', false),
            ],
        ]);

        // Search section
        $this->add([
            'name' => 'search_type',
            'type' => 'Select',
            'options' => [
                'element_group' => 'search',
                'label' => 'Search type', // @translate
                'info' => 'Select the type of search the main search field will perform', // @translate
                'value_options' => [
                    'sitewide' => 'This site', // @translate
                    'cross-site' => 'All sites', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'search_type',
                'value' => $settings->get('search_type', 'sitewide'),
            ],
        ]);

        $resourceNames = [
            'site_pages' => 'Site pages', // @translate
            'items' => 'Items', // @translate
            'item_sets' => 'Item sets', // @translate
        ];
        $this->add([
            'name' => 'search_resource_names',
            'type' => \Laminas\Form\Element\MultiCheckbox::class,
            'options' => [
                'element_group' => 'search',
                'label' => 'Search resources', // @translate
                'info' => 'Customize which types of resources will be searchable in the main search field.', // @translate
                'value_options' => $resourceNames,
            ],
            'attributes' => [
                'id' => 'search_resource_names',
                'value' => $settings->get('search_resource_names', ['site_pages', 'items']),
                'required' => false,
            ],
        ]);

        $this->add([
            'name' => 'vocabulary_scope',
            'type' => 'Select',
            'options' => [
                'element_group' => 'search',
                'label' => 'Advanced search vocabulary members', // @translate
                'info' => 'Limit the search options for property and class', // @translate
                'empty_option' => 'All vocabulary members', // @translate
                'value_options' => [
                    'sitewide' => 'Used by resources in this site', // @translate
                    'cross-site' => 'Used by any resource in the installation', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'vocabulary_scope',
                'value' => $settings->get('vocabulary_scope'),
            ],
        ]);

        $this->add([
            'type' => 'Omeka\Form\Element\ResourceTemplateSelect',
            'name' => 'search_apply_templates',
            'options' => [
                'element_group' => 'search',
                'label' => 'Templates', // @translate
                'info' => 'Select which templates to apply to the advanced search form.', // @translate
            ],
            'attributes' => [
                'multiple' => true,
                'class' => 'chosen-select',
                'data-placeholder' => 'Select templates', // @translate
                'value' => $settings->get('search_apply_templates', []),
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'search_restrict_templates',
            'options' => [
                'element_group' => 'search',
                'label' => 'Restrict to templates', // @translate
                'info' => 'Restrict search results to resources of the selected templates.', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('search_restrict_templates', false),
            ],
        ]);

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'locale',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'subnav_display',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'pagination_per_page',
            'required' => false,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'ToNull'],
            ],
            'validators' => [
                ['name' => 'Digits'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'browse_heading_property_term',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'browse_body_property_term',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'search_resource_names',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'vocabulary_scope',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'search_apply_templates',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'show_value_annotations',
            'required' => false,
            'allow_empty' => true,
        ]);
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

    public function setBrowseService(BrowseService $browseService)
    {
        $this->browseService = $browseService;
    }
}
