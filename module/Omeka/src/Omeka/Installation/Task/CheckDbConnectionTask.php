<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Result;

/**
 * Check database connection task.
 */
class CheckDbConnectionTask extends AbstractTask
{
    /**
     * Check whether the database configuration is valid.
     *
     * @param Result $result
     */
    public function perform(Result $result)
    {
        try {
            $this->getServiceLocator()->get('EntityManager')->getConnection()->connect();
        } catch (\Exception $e) {
            $result->addMessage($e->getMessage(), Result::MESSAGE_TYPE_ERROR, 'ConnectTask');
        }
    }
}
