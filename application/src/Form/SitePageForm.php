<?php
namespace Omeka\Form;

class SitePageForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Title'),
            ],
            'attributes' => [
                'id' => 'title',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('URL slug')
            ],
            'attributes' => [
                'id' => 'slug',
                'required' => false,
            ],
        ]);
    }
}
