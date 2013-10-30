<?php
namespace Omeka\Install\Task;

/**
 * Install Task interface
 *
 */
interface TaskInterface
{
    /**
     * Perform the installation task
     * 
     */
    public function perform();
}