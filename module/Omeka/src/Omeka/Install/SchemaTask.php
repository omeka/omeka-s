<?php
namespace Omeka\Install;
use Omeka\Install\InstallTaskInterface;

class SchemaTask implements InstallTaskInterface
{
    public function perform()
    {
        if(isset($_POST['submit'])) {
            
            $factory = new \Omeka\Service\EntityManagerFactory;
            $config = include 'config/application.config.php';
            $em = $factory->createEntityManager($config['entity_manager']);
            $conn = $em->getConnection();

            //check if tables already exist
            $tables = $conn->getSchemaManager()->listTableNames();
            if(!empty($tables)) {
                return;
            }
            $classes = unserialize(file_get_contents( __DIR__ . '/install_data/schema.txt'));

            //dbExport slaps 'DBPREFIX_' as the prefix onto all classes, so do the replace here for the real prefix
            foreach($classes as $index=>$sql) {
                $classes[$index] = str_replace('DBPREFIX_', $config['entity_manager']['table_prefix'], $classes[$index]);
            }
            
            foreach ($classes as $sql) {
                $conn->executeQuery($sql);
            }        
        }
    }
}