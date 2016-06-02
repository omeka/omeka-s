<?php
namespace Omeka\Form;

use Omeka\Permissions\Acl;
use Zend\Form\Form;

class UserForm extends Form
{
    /**
     * @var array
     */
    protected $options = [
        'include_role' => false,
        'include_admin_roles' => false,
        'include_is_active' => false,
    ];

    /**
     * @var Acl
     */
    protected $acl;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);
        $this->options = array_merge($this->options, $options);
    }

    public function init()
    {
        $this->add([
            'name' => 'o:email',
            'type' => 'Email',
            'options' => [
                'label' => 'Email', // @translate
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
                'label' => 'Display Name', // @translate
            ],
            'attributes' => [
                'id' => 'name',
                'required' => true,
            ],
        ]);

        if ($this->options['include_role']) {
            $excludeAdminRoles = !$this->options['include_admin_roles'];
            $roles = $this->getAcl()->getRoleLabels($excludeAdminRoles);
            $this->add([
                'name' => 'o:role',
                'type' => 'select',
                'options' => [
                    'label' => 'Role', // @translate
                    'value_options' => $roles,
                ],
                'attributes' => [
                    'id' => 'role',
                    'required' => true,
                ],
            ]);
        }

        if ($this->options['include_is_active']) {
            $this->add([
                'name' => 'o:is_active',
                'type' => 'checkbox',
                'options' => [
                    'label' => 'Is Active', // @translate
                ],
                'attributes' => [
                    'id' => 'is-active',
                ],
            ]);
        }
    }

    /**
     * @param Acl $acl
     */
    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl();
    }
}
