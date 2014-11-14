<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;
use Omeka\Module;
use Omeka\Service\Paginator;

class AddDefaultSettingsTask implements TaskInterface
{
    protected $defaultSettings = array(
        'version' => Module::VERSION,
        'pagination_per_page' => Paginator::PER_PAGE,
    );

    public function perform(Manager $manager)
    {
        $settings = $manager->getServiceLocator()->get('Omeka\Settings');
        foreach ($this->defaultSettings as $id => $value) {
            $settings->set($id, $value);
        }
    }
}
