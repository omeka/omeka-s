<?php
namespace Omeka\Form;

class UserForm extends AbstractForm
{
    protected $options = array('includeRole' => false);

    public function getFormName()
    {
        return 'user';
    }

    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'o:username',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Username'),
            ),
            'attributes' => array(
                'id' => 'username',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'o:name',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Name'),
            ),
            'attributes' => array(
                'id' => 'name',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'o:email',
            'type' => 'Email',
            'options' => array(
                'label' => $translator->translate('Email'),
            ),
            'attributes' => array(
                'id' => 'email',
                'required' => true,
            ),
        ));

        if ($this->getOption('includeRole')) {
            $this->add(array(
                'name' => 'o:role',
                'type' => 'select',
                'options' => array(
                    'label' => 'Role',
                    'value_options' => array(
                        'global_admin' => 'Global Admin',
                    ),
                ),
                'attributes' => array(
                    'id' => 'role',
                    'required' => true,
                ),
            ));
        }
    }
}
