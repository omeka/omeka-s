<?php 
namespace Omeka\Form;

class ItemForm extends ResourceForm
{
    public function buildForm()
    {
        parent::buildForm();

        $this->setAttribute('enctype', 'multipart/form-data');
    }
}
