<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

class CheckDirPermissionsTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $config = $installer->getServiceLocator()->get('Config');
        $basePath = $config['file_store']['local']['base_path'];
        if (null === $basePath) {
            $basePath = OMEKA_PATH . '/files';
        }
        if (!is_dir($basePath) || !is_writable($basePath)) {
            $installer->addError(sprintf('"%s" is not a writable directory.', $basePath));
            return;
        }
    }
}
