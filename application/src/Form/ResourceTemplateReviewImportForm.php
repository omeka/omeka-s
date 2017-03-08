<?php
namespace Omeka\Form;

use Zend\Form\Form;

class ResourceTemplateReviewImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'import',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
    }
}
