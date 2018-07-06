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

        $this->add([
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

        $this->add([
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
                'id' => 'attachment_link_type',
                'value' => $settings->get('attachment_link_type'),
            ],
        ]);

        $this->add([
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

        $headingTerm = $settings->get('browse_heading_property_term');
        $this->add([
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
        $this->add([
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
        $this->add([
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
        $this->add([
            'name' => 'show_user_bar',
            'type' => 'radio',
            'options' => [
                'label' => 'Show user bar on public views', // @translate
                'value_options' => [
                    '-1' => 'Never', // @translate
                    '0' => 'When identified', // @translate
                    '1' => 'Always', // @translate
                ],
            ],
            'attributes' => [
                'value' => $settings->get('show_user_bar', '0'),
            ],
        ]);
        $this->add([
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

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'locale',
            'allow_empty' => true,
            'attributes' => [
                'id' => 'locale',
            ],
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
