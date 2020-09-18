<?php
namespace Omeka\Form;

use Omeka\Form\Element\PropertySelect;
use Omeka\Settings\SiteSettings;
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

    public function init()
    {
        $settings = $this->getSiteSettings();

        // General section
        $this->add([
            'type' => 'fieldset',
            'name' => 'general',
            'options' => [
                'label' => 'General', // @translate
            ],
        ]);
        $generalFieldset = $this->get('general');
        // o:assign_new_items element is a pseudo-setting that's ultimately set
        // as a property of the site and not as a site setting.
        $generalFieldset->add([
            'name' => 'o:assign_new_items',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Auto-assign new items', // @translate
                'info' => 'Select this if you want new items to be automatically assigned to this site. Note that item owners may unassign their items at any time.', // @translate
            ],
            'attributes' => [
                'id' => 'assign_new_items',
                'value' => true,
            ],
        ]);
        $generalFieldset->add([
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
                'id' => 'attachment_link_type',
                'value' => $settings->get('attachment_link_type'),
            ],
        ]);
        $generalFieldset->add([
            'name' => 'item_media_embed',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Embed media on item pages', // @translate
            ],
            'attributes' => [
                'id' => 'item_media_embed',
                'value' => (bool) $settings->get('item_media_embed', false),
            ],
        ]);
        $generalFieldset->add([
            'name' => 'show_page_pagination',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Show page pagination', // @translate
                'info' => 'Show pagination that helps users follow a linear narrative through a site.', // @translate
            ],
            'attributes' => [
                'id' => 'show_page_pagination',
                'value' => $settings->get('show_page_pagination', true),
            ],
        ]);
        $generalFieldset->add([
            'name' => 'show_user_bar',
            'type' => 'radio',
            'options' => [
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
        $generalFieldset->add([
            'name' => 'disable_jsonld_embed',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Disable JSON-LD embed', // @translate
                'info' => 'By default, Omeka embeds JSON-LD in resource browse and show pages for the purpose of machine-readable metadata discovery. Check this to disable embedding.', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('disable_jsonld_embed'),
                'id' => 'disable_jsonld_embed',
            ],
        ]);
        $generalFieldset->add([
            'name' => 'locale',
            'id' => 'locale',
            'type' => 'Omeka\Form\Element\LocaleSelect',
            'options' => [
                'label' => 'Locale', // @translate
                'info' => 'Locale/language code for this site. Leave blank to use the global locale setting.', // @translate
            ],
            'attributes' => [
                'id' => 'locale',
                'value' => $settings->get('locale'),
                'class' => 'chosen-select',
            ],
        ]);

        // Browse section
        $this->add([
            'type' => 'fieldset',
            'name' => 'browse',
            'options' => [
                'label' => 'Browse', // @translate
            ],
        ]);
        $browseFieldset = $this->get('browse');
        $browseFieldset->add([
            'name' => 'browse_attached_items',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Restrict browse to attached items', // @translate
            ],
            'attributes' => [
                'id' => 'browse_attached_items',
                'value' => (bool) $settings->get('browse_attached_items', false),
            ],
        ]);
        $browseFieldset->add([
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => [
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
        $browseFieldset->add([
            'name' => 'browse_heading_property_term',
            'type' => PropertySelect::class,
            'options' => [
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
        $browseFieldset->add([
            'name' => 'browse_body_property_term',
            'type' => PropertySelect::class,
            'options' => [
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

        // Show section
        $this->add([
            'type' => 'fieldset',
            'name' => 'show',
            'options' => [
                'label' => 'Show', // @translate
            ],
        ]);
        $showFieldset = $this->get('show');
        $showFieldset->add([
            'name' => 'show_attached_pages',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Show attached pages', // @translate
                'info' => 'Show site pages to which an item is attached on the public item show page.', // @translate
            ],
            'attributes' => [
                'id' => 'show_attached_pages',
                'value' => (bool) $settings->get('show_attached_pages', true),
            ],
        ]);

        // Search section
        $this->add([
            'type' => 'fieldset',
            'name' => 'search',
            'options' => [
                'label' => 'Search', // @translate
            ],
        ]);
        $searchFieldset = $this->get('search');

        $searchFieldset->add([
            'name' => 'search_type',
            'type' => 'Select',
            'options' => [
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
        $searchFieldset->add([
            'name' => 'search_resource_names',
            'type' => \Laminas\Form\Element\MultiCheckbox::class,
            'options' => [
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
        $searchFieldset->add([
            'type' => 'Omeka\Form\Element\ResourceTemplateSelect',
            'name' => 'search_apply_templates',
            'options' => [
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
        $searchFieldset->add([
            'type' => 'checkbox',
            'name' => 'search_restrict_templates',
            'options' => [
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
        $inputFilter->get('general')->add([
            'name' => 'locale',
            'allow_empty' => true,
            'attributes' => [
                'id' => 'locale',
            ],
        ]);
        $inputFilter->get('browse')->add([
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
        $inputFilter->get('browse')->add([
            'name' => 'browse_heading_property_term',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->get('browse')->add([
            'name' => 'browse_body_property_term',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->get('search')->add([
            'name' => 'search_resource_names',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->get('search')->add([
            'name' => 'search_apply_templates',
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
}
