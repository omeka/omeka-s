<?php
namespace Omeka\Install\Task;

use Omeka\Install\Task\AbstractTask;
use Omeka\Install\Task\TaskInterface;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Task to install the basic Omeka schema
 * @author patrickmj
 *
 */
class Schema extends AbstractTask implements TaskInterface
{
    protected $taskName = "Install tables";
    
    public function perform()
    {
        $conn = $this->getServiceLocator()->get('EntityManager')->getConnection();
        $config = $this->getServiceLocator()->get('Config');
        //check if tables already exist
        $tables = $conn->getSchemaManager()->listTableNames();
        if(!empty($tables)) {
            $this->result->addMessage('Omeka is already installed.', TaskResult::TASK_RESULT_ERROR);
            $this->result->setSuccess(false);
            return;
        }
        
        if(!is_readable($this->installDataPath . '/schema.sql' )) {
            $this->result->addMessage('Could not read the schema installation file.',  TaskResult::TASK_RESULT_ERROR);
            $this->result->setSuccess(false);
            return;   
        }
        //$classes = unserialize(file_get_contents($this->installDataPath . '/schema.txt'));
        $queries = file($this->installDataPath . '/schema.sql');
        if(!is_array($queries)) {
            $this->result->addMessage('Could not read the schema installation file.', TaskResult::TASK_RESULT_ERROR);
            $this->result->setSuccess(false);
            return;            
        }
        
        //database export slaps 'DBPREFIX_' as the prefix onto all classes, so do the replace here for the real prefix
        foreach($queries as $sql) {
            $rePrefixedSql = str_replace('DBPREFIX_', $config['entity_manager']['table_prefix'], $sql);
            try {
                $conn->executeQuery($rePrefixedSql);
            } catch(\Doctrine\DBAL\DBALException $e) {
                $this->result->addExceptionMessage($e, 'A problem occurred while creating tables.');
                $this->result->setSuccess(false);
            }            
        }
        $this->result->addMessage('Tables installed ok.'); 
        $this->result->setSuccess(true);
    }
}