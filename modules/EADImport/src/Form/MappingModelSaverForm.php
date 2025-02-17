<?php

namespace EADImport\Form;

use Laminas\Form\Form;

class MappingModelSaverForm extends Form
{
    public function init()
    {
        $this->setAttribute('action', 'mapping-model/save');

        $this->add([
            'name' => 'model_name',
            'type' => 'text',
            'options' => [
                'label' => 'Mapping model name', //@translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'mapping',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);
    }
}
