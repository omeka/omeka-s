<?php
namespace Omeka\Form;

class UserForm extends AbstractForm
{
    protected $options = array(
        'include_role' => false,
        'include_admin_roles' => false
    );

    public function buildForm()
    {
        $translator = $this->getTranslator();

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
        $this->add(array(
            'name' => 'o:name',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Display Name'),
            ),
            'attributes' => array(
                'id' => 'name',
                'required' => true,
            ),
        ));

        if ($this->getOption('include_role')) {
            $excludeAdminRoles = !$this->getOption('include_admin_roles');
            $roles = $this->getServiceLocator()->get('Omeka\Acl')->getRoleLabels($excludeAdminRoles);
            $this->add(array(
                'name' => 'o:role',
                'type' => 'select',
                'options' => array(
                    'label' => $translator->translate('Role'),
                    'value_options' => $roles,
                ),
                'attributes' => array(
                    'id' => 'role',
                    'required' => true,
                ),
            ));
        }

        if ($this->getOption('include_is_active')) {
            $this->add(array(
                'name' => 'o:is_active',
                'type' => 'checkbox',
                'options' => array(
                    'label' => $translator->translate('Is Active'),
                ),
                'attributes' => array(
                    'id' => 'is-active',
                ),
            ));
        }
    }
}
