<?php
namespace Omeka\Install;

use Omeka\Install\InstallTaskAbstract;
use Omeka\Install\InstallTaskInterface;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorInterface;

class SchemaTask extends InstallTaskAbstract implements InstallTaskInterface
{
    public function perform()
    {
        $conn = $this->serviceLocator->get('EntityManager')->getConnection();
        $config = $this->serviceLocator->get('ApplicationConfig');
        
        //check if tables already exist
        $tables = $conn->getSchemaManager()->listTableNames();
        if(!empty($tables)) {
            $this->addMessage('Omeka is already installed.');
            return;
        }
        if(isset($_POST['submit'])) {

            try {
                $classes = unserialize(file_get_contents( __DIR__ . '/install_data/schema.txt'));
            } catch(Exception $e) {
                $this->addMessage($e->getMessage(), $e->getCode());
                return;
            }
            
            //dbExport slaps 'DBPREFIX_' as the prefix onto all classes, so do the replace here for the real prefix
            foreach($classes as $index=>$sql) {
                $classes[$index] = str_replace('DBPREFIX_', $config['entity_manager']['table_prefix'], $classes[$index]);
            }

            foreach ($classes as $sql) {
                try {
                    $conn->executeQuery($sql);                    
                } catch(Exception $e) {
                    $this->addMessage($e->getMessage(), $e->getCode());
                    $this->setFail();
                }
            }        
        }
    }
}