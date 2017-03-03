<?php
namespace Omeka\Form;

use Zend\Form\Form;

class ResourceTemplateReviewImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'resource_template',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);
    }
}
