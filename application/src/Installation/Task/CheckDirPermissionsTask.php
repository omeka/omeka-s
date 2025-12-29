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
            $parent = dirname($basePath);
            if (is_dir($parent) && is_writable($parent)) {
                $installer->addWarning(sprintf('"%s" is not a writable directory. It will be created during installation.', $basePath));
            } else {
                $installer->addError(sprintf('"%s" (nor its parent) is a writable directory.', $basePath));
            }
            return;
        }
    }
}
