<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

interface TaskInterface
{
    /**
     * Perform the installation task.
     */
    public function perform(Installer $installer);
}
