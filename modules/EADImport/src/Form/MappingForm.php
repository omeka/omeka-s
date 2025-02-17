<?php

namespace EADImport\Form;

use Laminas\Form\Form;

class MappingForm extends Form
{
    public function init()
    {
        $this->setAttribute('action', 'import');
        $this->setAttribute('id', 'mappingForm');
    }
}
