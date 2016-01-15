<?php
namespace Omeka\Form;

class Form extends AbstractForm
{
    public function buildForm()
    {
        $this->setAttribute('id', 'site-form');
    }
}