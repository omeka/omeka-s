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
    }
}
