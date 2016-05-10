<?php
namespace Omeka\Form;

class SiteSettingsForm extends AbstractForm
{
    public function buildForm()
    {
        $settings = $this->getServiceLocator()->get('Omeka\SiteSettings');

        $this->add([
            'name' => 'browse_attached_items',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Restrict browse to attached items', // @translate
            ],
            'attributes' => [
                'value' => (bool) $settings->get('browse_attached_items', false),
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
                'value' => $settings->get('attachment_link_type'),
            ]
        ]);
    }
}
