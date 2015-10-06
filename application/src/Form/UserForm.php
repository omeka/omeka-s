<?php
namespace Omeka\Form;

class UserForm extends AbstractForm
{
    protected $options = [
        'include_role' => false,
        'include_admin_roles' => false
    ];

    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:email',
            'type' => 'Email',
            'options' => [
                'label' => $translator->translate('Email'),
            ],
            'attributes' => [
                'id' => 'email',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:name',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Display Name'),
            ],
            'attributes' => [
                'id' => 'name',
                'required' => true,
            ],
        ]);

        if ($this->getOption('include_role')) {
            $excludeAdminRoles = !$this->getOption('include_admin_roles');
            $roles = $this->getServiceLocator()->get('Omeka\Acl')->getRoleLabels($excludeAdminRoles);
            $this->add([
                'name' => 'o:role',
                'type' => 'select',
                'options' => [
                    'label' => $translator->translate('Role'),
                    'value_options' => $roles,
                ],
                'attributes' => [
                    'id' => 'role',
                    'required' => true,
                ],
            ]);
        }

        if ($this->getOption('include_is_active')) {
            $this->add([
                'name' => 'o:is_active',
                'type' => 'checkbox',
                'options' => [
                    'label' => $translator->translate('Is Active'),
                ],
                'attributes' => [
                    'id' => 'is-active',
                ],
            ]);
        }
    }
}
