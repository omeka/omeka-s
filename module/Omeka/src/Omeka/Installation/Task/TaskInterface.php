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
     *
     * @param Result $result
     */
    public function perform(Result $result);
}
