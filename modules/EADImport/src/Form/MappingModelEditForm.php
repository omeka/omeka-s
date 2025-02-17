<?php

namespace EADImport\Form;

use Laminas\Form\Form;

class MappingModelEditForm extends Form
{
    public function init()
    {
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
    }
}
