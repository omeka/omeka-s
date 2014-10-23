<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;

interface TaskInterface
{
    /**
     * Perform the installation task.
     */
    public function perform(Manager $manager);
}

