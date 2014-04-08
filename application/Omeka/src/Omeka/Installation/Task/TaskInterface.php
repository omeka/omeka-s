<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Result;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Installation task interface.
 */
interface TaskInterface extends ServiceLocatorAwareInterface
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
