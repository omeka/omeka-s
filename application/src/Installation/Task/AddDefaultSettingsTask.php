<?php
namespace Omeka\Installation\Task;

use Omeka\Form\SettingForm;
use Omeka\Installation\Installer;
use Omeka\Module;
use Omeka\Stdlib\Paginator;

class AddDefaultSettingsTask implements TaskInterface
{
    protected $defaultSettings = [
        'version' => Module::VERSION,
        'pagination_per_page' => Paginator::PER_PAGE,
        'media_type_whitelist' => SettingForm::MEDIA_TYPE_WHITELIST,
        'extension_whitelist' => SettingForm::EXTENSION_WHITELIST,
        'version_notifications' => '1',
    ];

    public function perform(Installer $installer)
    {
        $vars = $installer->getVars('Omeka\Installation\Task\AddDefaultSettingsTask');
        $this->defaultSettings['administrator_email'] = $vars['administrator_email'];
        $this->defaultSettings['installation_title'] = $vars['installation_title'];
        $this->defaultSettings['time_zone'] = $vars['time_zone'];
        $this->defaultSettings['locale'] = $vars['locale'];

        $settings = $installer->getServiceLocator()->get('Omeka\Settings');
        foreach ($this->defaultSettings as $id => $value) {
            $settings->set($id, $value);
        }
    }
}
