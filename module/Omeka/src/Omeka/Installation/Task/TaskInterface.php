<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Result;

/**
 * Installation task interface.
 */
interface TaskInterface
{
    /**
     * Perform the installation task.
     */
    public function perform();

    /**
     * Get the human-readable name of the task.
     *
     * @return string
     */
    public function getName();
}
