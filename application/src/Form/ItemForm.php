<?php 
namespace Omeka\Form;

use Omeka\Form\Element\ResourceSelect;

class ItemForm extends ResourceForm
{
    public function buildForm()
    {
        parent::buildForm();

        $this->setAttribute('enctype', 'multipart/form-data');
    }
}
