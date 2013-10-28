<?php
namespace Omeka\Install\Task;

use Omeka\Install\Task\TaskInterface;
use Omeka\Install\Task\AbstractTask;

/**
 * Installation task to simply check if the connection exists
 * 
 * Allows message to be passed back to the installer if the db configuration is wrong
 * @author patrickmj
 *
 */
class Connection extends AbstractTask implements TaskInterface
{
    protected $taskName = "Database connection";
    
    public function perform()
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $conn = $em->getConnection();
        //The exact Exception is impossible to predict since it depends on
        //how the EntityManager is actually making the connection, so we
        //have to catch at the most general level
        try {
            $conn->connect();
        } catch(\Exception $e) {
            $this->result->addExceptionMessage($e, "The database is not correctly configured. Check the settings in application.config.php.");
            $this->result->setSuccess(false);
        }
        $this->result->setSuccess(true);
    }
} 