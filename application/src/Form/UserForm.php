<?php
namespace Omeka\Form;

use Omeka\Form\Element\Columns;
use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\SiteSelect;
use Omeka\Form\Element\BrowseDefaults;
use Omeka\Permissions\Acl;
use Omeka\Settings\Settings;
use Omeka\Settings\UserSettings;
use Laminas\Form\Form;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

class UserForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var array
     */
    protected $options = [
        'include_role' => false,
        'include_admin_roles' => false,
        'include_is_active' => false,
        'current_password' => false,
        'include_password' => false,
        'include_key' => false,
    ];

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var UserSettings
     */
    protected $userSettings;

    protected $browseService;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $this->add([
            'name' => 'user-information',
            'type' => 'fieldset',
        ]);
        $this->add([
            'name' => 'user-settings',
            'type' => 'fieldset',
        ]);
        $this->add([
            'name' => 'change-password',
            'type' => 'fieldset',
        ]);
        $this->add([
            'name' => 'edit-keys',
            'type' => 'fieldset',
        ]);
        $this->get('user-information')->add([
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
        $this->get('user-information')->add([
            'name' => 'o:name',
            'type' => 'Text',
            'options' => [
                'label' => 'Display name', // @translate
            ],
            'attributes' => [
                'id' => 'name',
                'required' => true,
            ],
        ]);

        if ($this->getOption('include_role')) {
            $excludeAdminRoles = !$this->getOption('include_admin_roles');
            $roles = $this->getAcl()->getRoleLabels($excludeAdminRoles);
            $this->get('user-information')->add([
                'name' => 'o:role',
                'type' => 'select',
                'options' => [
                    'label' => 'Role', // @translate
                    'empty_option' => 'Select role…', // @translate
                    'value_options' => $roles,
                ],
                'attributes' => [
                    'id' => 'role',
                    'required' => true,
                ],
            ]);
        }

        if ($this->getOption('include_is_active')) {
            $this->get('user-information')->add([
                'name' => 'o:is_active',
                'type' => 'checkbox',
                'options' => [
                    'label' => 'Is active', // @translate
                ],
                'attributes' => [
                    'id' => 'is-active',
                ],
            ]);
        }

        $userId = $this->getOption('user_id');
        $locale = $userId ? $this->userSettings->get('locale', null, $userId) : null;
        if (null === $locale) {
            $locale = $this->settings->get('locale');
        }

        $settingsFieldset = $this->get('user-settings');
        $settingsFieldset->setOption('element_groups', [
            'columns' => 'Admin browse columns', // @translate
            'browse_defaults' => 'Admin browse defaults', // @translate
        ]);
        $settingsFieldset->add([
            'name' => 'locale',
            'type' => 'Omeka\Form\Element\LocaleSelect',
            'options' => [
                'label' => 'Locale', // @translate
                'info' => 'Global locale/language code for all interfaces.', // @translate
            ],
            'attributes' => [
                'value' => $locale,
                'class' => 'chosen-select',
                'id' => 'locale',
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'default_resource_template',
            'type' => ResourceSelect::class,
            'attributes' => [
                'value' => $userId ? $this->userSettings->get('default_resource_template', null, $userId) : '',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a template', // @translate
                'id' => 'default_resource_template',
            ],
            'options' => [
                'label' => 'Default resource template', // @translate
                'empty_option' => '',
                'resource_value_options' => [
                    'resource' => 'resource_templates',
                    'query' => [
                        'sort_by' => 'label',
                    ],
                    'option_text_callback' => function ($resourceTemplate) {
                        return $resourceTemplate->label();
                    },
                ],
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'default_item_sets',
            'type' => ItemSetSelect::class,
            'attributes' => [
                'value' => $userId ? $this->userSettings->get('default_item_sets', null, $userId) : [],
                'class' => 'chosen-select',
                'data-placeholder' => 'Select item sets', // @translate
                'multiple' => true,
                'id' => 'default_item_sets',
            ],
            'options' => [
                'label' => 'Default item sets for items', // @translate
                'empty_option' => '',
                'query' => ['is_open' => true],
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'default_item_sites',
            'type' => SiteSelect::class,
            'attributes' => [
                'value' => $userId ? $this->userSettings->get('default_item_sites', null, $userId) : [],
                'class' => 'chosen-select',
                'data-placeholder' => 'Select sites', // @translate
                'multiple' => true,
                'id' => 'default_sites',
            ],
            'options' => [
                'label' => 'Default sites for items', // @translate
                'empty_option' => '',
                'filter_resource_representations' => function ($sites) {
                    // The user must have permission to assign items to the site.
                    foreach ($sites as $index => $site) {
                        if (!$site->userIsAllowed('can-assign-items')) {
                            unset($sites[$index]);
                        }
                    }
                    return $sites;
                },
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'columns_admin_items',
            'type' => Columns::class,
            'options' => [
                'element_group' => 'columns',
                'label' => 'Item browse columns', // @translate
                'columns_context' => 'admin',
                'columns_resource_type' => 'items',
                'columns_user_id' => $userId,
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'columns_admin_item_sets',
            'type' => Columns::class,
            'options' => [
                'element_group' => 'columns',
                'label' => 'Item set browse columns', // @translate
                'columns_context' => 'admin',
                'columns_resource_type' => 'item_sets',
                'columns_user_id' => $userId,
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'columns_admin_media',
            'type' => Columns::class,
            'options' => [
                'element_group' => 'columns',
                'label' => 'Media browse columns', // @translate
                'columns_context' => 'admin',
                'columns_resource_type' => 'media',
                'columns_user_id' => $userId,
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'columns_admin_sites',
            'type' => Columns::class,
            'options' => [
                'element_group' => 'columns',
                'label' => 'Site browse columns', // @translate
                'columns_context' => 'admin',
                'columns_resource_type' => 'sites',
                'columns_user_id' => $userId,
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'browse_defaults_admin_items',
            'type' => BrowseDefaults::class,
            'options' => [
                'element_group' => 'browse_defaults',
                'label' => 'Item browse defaults', // @translate
                'browse_defaults_context' => 'admin',
                'browse_defaults_resource_type' => 'items',
                'browse_defaults_user_id' => $userId,
            ],
            'attributes' => [
                'value' => json_encode($this->browseService->getBrowseConfig('admin', 'items', $userId)),
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'browse_defaults_admin_item_sets',
            'type' => BrowseDefaults::class,
            'options' => [
                'element_group' => 'browse_defaults',
                'label' => 'Item set browse defaults', // @translate
                'browse_defaults_context' => 'admin',
                'browse_defaults_resource_type' => 'item_sets',
                'browse_defaults_user_id' => $userId,
            ],
            'attributes' => [
                'value' => json_encode($this->browseService->getBrowseConfig('admin', 'item_sets', $userId)),
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'browse_defaults_admin_media',
            'type' => BrowseDefaults::class,
            'options' => [
                'element_group' => 'browse_defaults',
                'label' => 'Media browse defaults', // @translate
                'browse_defaults_context' => 'admin',
                'browse_defaults_resource_type' => 'media',
                'browse_defaults_user_id' => $userId,
            ],
            'attributes' => [
                'value' => json_encode($this->browseService->getBrowseConfig('admin', 'media', $userId)),
            ],
        ]);
        $settingsFieldset->add([
            'name' => 'browse_defaults_admin_sites',
            'type' => BrowseDefaults::class,
            'options' => [
                'element_group' => 'browse_defaults',
                'label' => 'Site browse defaults', // @translate
                'browse_defaults_context' => 'admin',
                'browse_defaults_resource_type' => 'sites',
                'browse_defaults_user_id' => $userId,
            ],
            'attributes' => [
                'value' => json_encode($this->browseService->getBrowseConfig('admin', 'sites', $userId)),
            ],
        ]);

        if ($this->getOption('include_password')) {
            if ($this->getOption('current_password')) {
                $this->get('change-password')->add([
                    'name' => 'current-password',
                    'type' => 'password',
                    'options' => [
                        'label' => 'Current password', // @translate
                    ],
                    'attributes' => [
                        'id' => 'current-password',
                    ],
                ]);
            }
            $this->get('change-password')->add([
                'name' => 'password-confirm',
                'type' => 'Omeka\Form\Element\PasswordConfirm',
            ]);
            $this->get('change-password')->get('password-confirm')->setLabels(
                'New password', // @translate
                'Confirm new password' // @translate
            );
        }

        if ($this->getOption('include_key')) {
            $this->get('edit-keys')->add([
                'name' => 'new-key-label',
                'type' => 'Text',
                'options' => [
                    'label' => 'New key label', // @translate
                ],
                'attributes' => [
                    'id' => 'new-key-label',
                ],
            ]);
        }

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        // separate input filter stuff so that the event work right
        $inputFilter = $this->getInputFilter();

        $inputFilter->get('user-settings')->add([
            'name' => 'locale',
            'allow_empty' => true,
        ]);
        $inputFilter->get('user-settings')->add([
            'name' => 'default_resource_template',
            'allow_empty' => true,
        ]);
        $inputFilter->get('user-settings')->add([
            'name' => 'default_item_sets',
            'allow_empty' => true,
        ]);
        $inputFilter->get('user-settings')->add([
            'name' => 'default_item_sites',
            'allow_empty' => true,
        ]);

        if ($this->getOption('include_key')) {
            $inputFilter->get('edit-keys')->add([
                'name' => 'new-key-label',
                'required' => false,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'max' => 255,
                        ],
                    ],
                ],
            ]);
        }

        $filterEvent = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($filterEvent);
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
        return $this->acl;
    }

    /**
     * @param Settings $settings
     */
    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param UserSettings $userSettings
     */
    public function setUserSettings(UserSettings $userSettings)
    {
        $this->userSettings = $userSettings;
    }

    /**
     * @return UserSettings
     */
    public function getUserSettings()
    {
        return $this->userSettings;
    }

    public function setBrowseService($browseService)
    {
        $this->browseService = $browseService;
    }
}
