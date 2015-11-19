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
    }
}
