<?php
namespace Omeka\Form;

class SiteSettingsForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        $settings = $this->getServiceLocator()->get('Omeka\SiteSettings');

        $this->add([
            'name' => 'browse_attached_items',
            'type' => 'checkbox',
            'options' => [
                'label' => $translator->translate('Restrict browse to attached items'),
            ],
            'attributes' => [
                'value' => (bool) $settings->get('browse_attached_items', false),
            ],
        ]);

        $this->add([
            'name' => 'attachment_link_type',
            'type' => 'Select',
            'options' => [
                'label' => $translator->translate('Attachment link type'),
                'value_options' => [
                    'item' => $translator->translate('Item page'),
                    'media' => $translator->translate('Media page'),
                    'original' => $translator->translate('Direct link to file'),
                ],
            ],
            'attributes' => [
                'value' => $settings->get('attachment_link_type'),
            ]
        ]);
    }
}
