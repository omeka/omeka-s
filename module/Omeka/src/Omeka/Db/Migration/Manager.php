<?php
namespace Omeka\Db\Migration;

use GlobIterator;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Migration manager.
 */
class Manager implements ServiceLocatorAwareInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Create the migration manager, passing configuration.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * Set config options from array.
     *
     * @param array $config
     */
    public function setConfig($config)
    {
        if (array_key_exists('path', $config)) {
            $this->path = $config['path'];
        }

        if (array_key_exists('namespace', $config)) {
            $this->namespace = $config['namespace'];
        }

        if (array_key_exists('entity', $config)) {
            $this->entity = $config['entity'];
        }
    }

    /**
     * Perform the upgrade operation on all pending migrations.
     */
    public function upgrade()
    {
        $toPerform = $this->getMigrationsToPerform();
        $conn = $this->getServiceLocator()->get('EntityManager')->getConnection();

        foreach ($toPerform as $version => $migrationInfo) {
            $migration = $this->loadMigration(
                $migrationInfo['path'], $migrationInfo['class']);
            $migration->up($conn);
            $this->recordMigration($version);
        }
    }

    /**
     * Record a migration as complete in the database.
     *
     * @param string $version Version to record
     */
    public function recordMigration($version)
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $conn = $em->getConnection();
        $tableName = $this->getTableName($em);
        $conn->insert($tableName, array('version' => $version));
    }

    /**
     * Load a migration file and instantiate the class.
     *
     * @param string $path Path to the migration
     * @param string $class Fully-qualified name of the migration class
     * @return MigrationInterface
     */ 
    public function loadMigration($path, $class)
    {
        require $path;

        if (!class_exists($class, false)) {
            throw new Exception\ClassNotFoundException('Migration file did not contain the expected class');
        }

        return new $class;
    }

    /**
     * Get pending migrations.
     *
     * @return array
     */
    public function getMigrationsToPerform()
    {
        $available = $this->getAvailableMigrations();
        $completed = $this->getCompletedMigrations();
        $diff = array_diff_key($available, array_flip($completed));
        ksort($diff);
        return $diff;
    }

    /**
     * Get already-performed migrations.
     *
     * @return array
     */
    public function getCompletedMigrations()
    {
        $sl = $this->getServiceLocator();
        $em = $sl->get('EntityManager');
        $conn = $em->getConnection();

        $tableName = $this->getTableName($em);
        $completed = $conn->fetchArray("SELECT version FROM $tableName");

        if (!$completed) {
            $completed = array();
        }

        return $completed;
    }

    /**
     * Get available migrations.
     *
     * @return array
     */
    public function getAvailableMigrations()
    {
        $migrations = array();
        $globPattern = $this->path . DIRECTORY_SEPARATOR . '*.php';
        $regexPattern = '/^(\d+)_(\w+)\.php$/';
        $iterator = new GlobIterator($globPattern);
        foreach ($iterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (preg_match($regexPattern, $fileInfo->getFilename(), $matches)) {
                $version = $matches[1];
                $class = $this->namespace . '\\' . $matches[2];
                $migrations[$version] = array(
                    'path' => $fileInfo->getPathname(),
                    'class' => $class,
                );
            }
        }

        return $migrations;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get the migration table name from the EntityManager.
     *
     * @param Doctrine\ORM\EntityManager
     * @return string Table name
     */
    protected function getTableName($em)
    {
        return $em->getClassMetadata($this->entity)->getTableName();
    }
}
