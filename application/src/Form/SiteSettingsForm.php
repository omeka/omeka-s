<?php
namespace Omeka\Form;

use Omeka\Form\Element\PropertySelect;
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
                'label' => 'Show page pagination',
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
                'info' => 'Use this property for the heading of each resource on a browse page. Default is "Dublin Core: Title".', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'browse_heading_property_term',
                'value' => $headingTerm ? $headingTerm : 'dcterms:title',
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
                'info' => 'Use this property for the body of each resource on a browse page. Default is "Dublin Core: Description".', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'browse_body_property_term',
                'value' => $bodyTerm ? $bodyTerm : 'dcterms:description',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
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
                'label' => 'Restrict to templates',
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
