<?php
namespace Omeka\Install;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;


/**
 * Exports the SQL needed to install a new Omeka site.
 * Used to package up Omeka, but should be removed from dist.
 * 
 * @author patrickmj
 *
 */

class DbExporter
{
    public function export()
    {
        ini_set('display_errors', 1);
        
        require_once '../../../../../vendor/autoload.php';
        
        $factory = new \Omeka\Service\EntityManagerFactory;
        
        //Not a convenient way to dig up the config data programatically.
        //Since this is only internal and not distributed, just copy from application.config.php.
        //Important thing is to make the prefix DBPREFIX_ so the installer
        //can replace with the real table prefix
        $config = array('conn' => array(
                                'user'        => 'root',
                                'password'    => 'root',
                                'dbname'      => 'omeka3',
                                'driver'      => 'pdo_mysql',
                                'host'        => 'localhost',
                                'port'        => null,
                                'unix_socket' => null,
                                'charset'     => null,
                            ),
                            'table_prefix' => 'DBPREFIX_',
                            'is_dev_mode'  => true,
                        );
        $em = $factory->createEntityManager($config);
        $tool = new SchemaTool($em);
        
        //As models are added to the core, need to add them here, too
        $classes = array();
        $classes[] = $em->getClassMetadata('Omeka\\Model\\File');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Item');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\ItemSet');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Media');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Property');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Resource');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\ResourceClass');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\ResourceClassProperty');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Site');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\SiteResource');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\User');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Value');
        $classes[] = $em->getClassMetadata('Omeka\\Model\\Vocabulary');
        
        $sql = $tool->getCreateSchemaSql($classes);
        file_put_contents('install_data/schema.txt', serialize($sql));        
    }
}

$exporter = new DbExporter();
$exporter->export();