<?php
namespace CustomVocab\Form;

use Laminas\Form\Form;

class CustomVocabImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'import_file',
            'type' => 'file',
            'options' => [
                'label' => 'Custom vocab file', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'file',
            ],
        ]);
    }
}
