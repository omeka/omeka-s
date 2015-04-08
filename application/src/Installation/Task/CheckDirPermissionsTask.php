<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

class CheckDirPermissionsTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $filesDir = OMEKA_PATH . '/files';
        if (!is_dir($filesDir) || !is_writable($filesDir)) {
            $installer->addError(sprintf('"%s" is not a writable directory.', $filesDir));
            return;
        }
    }
}
