<?php
namespace Omeka\Form;

use Laminas\Form\Form;

class VocabularyUpdateForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'diff',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);
    }
}
