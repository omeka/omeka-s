<?php
namespace Omeka\Install;

use Omeka\Install\TaskInterface;
use Omeka\Install\TaskAbstract;

class TaskConnectDb extends TaskAbstract implements TaskInterface
{
    protected $taskName = "Database connection";
    
    public function perform()
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $conn = $em->getConnection();
        try {
            $conn->connect();
        } catch(\Exception $e) {
            $this->result->addExceptionMessage($e, "The database is not correctly configured. Check the settings in application.config.php.");
            $this->result->setSuccess(false);
        }
        $this->result->addMessage("The database settings are correctly configured.");
        $this->result->setSuccess(true);
    }
} 