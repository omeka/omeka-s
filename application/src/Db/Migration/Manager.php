<?php
namespace Omeka\Db\Migration;

use Doctrine\DBAL\Connection;
use GlobIterator;
use PDO;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Migration manager.
 */
class Manager
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var Connection
     */
    protected $conn;

    /**
     * Create the migration manager, passing configuration.
     *
     * @param array $config
     * @param Connection $conn;
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($config, Connection $conn, ServiceLocatorInterface $serviceLocator)
    {
        $this->setConfig($config);
        $this->conn = $conn;
        $this->serviceLocator = $serviceLocator;
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
    }

    /**
     * Perform the upgrade operation on all pending migrations.
     */
    public function upgrade()
    {
        $toPerform = $this->getMigrationsToPerform();

        foreach ($toPerform as $version => $migrationInfo) {
            $migration = $this->loadMigration(
                $migrationInfo['path'], $migrationInfo['class']);
            $migration->up($this->conn);
            $this->recordMigration($version);
        }

        $this->clearDoctrineCache();
    }

    /**
     * Record a migration as complete in the database.
     *
     * @param string $version Version to record
     */
    public function recordMigration($version)
    {
        $this->conn->insert('migration', ['version' => $version]);
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
        require_once $path;

        if (!class_exists($class, false)
            || !is_subclass_of($class, 'Omeka\Db\Migration\MigrationInterface')
        ) {
            throw new Exception\ClassNotFoundException(
                $this->getTranslator()->translate('Migration file did not contain the expected class')
            );
        }

        if (is_subclass_of($class, 'Omeka\Db\Migration\ConstructedMigrationInterface')) {
            $migration = $class::create($this->serviceLocator);
        } else {
            $migration = new $class;
        }

        return $migration;
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
        $completed = $this->conn
            ->executeQuery("SELECT version FROM migration")
            ->fetchAll(PDO::FETCH_COLUMN);
        if (!$completed) {
            $completed = [];
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
        $migrations = [];
        $globPattern = $this->path . DIRECTORY_SEPARATOR . '*.php';
        $regexPattern = '/^(\d+)_(\w+)\.php$/';
        $iterator = new GlobIterator($globPattern);
        foreach ($iterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (preg_match($regexPattern, $fileInfo->getFilename(), $matches)) {
                $version = $matches[1];
                $class = $this->namespace . '\\' . $matches[2];
                $migrations[$version] = [
                    'path' => $fileInfo->getPathname(),
                    'class' => $class,
                ];
            }
        }

        return $migrations;
    }

    /**
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->serviceLocator->get('MvcTranslator');
        }
        return $this->translator;
    }

    /**
     * Clear Doctrine's cache to prevent errors after upgrade.
     */
    protected function clearDoctrineCache()
    {
        $em = $this->serviceLocator->get('Omeka\EntityManager');
        $cache = $em->getConfiguration()->getMetadataCacheImpl();

        if (!$cache) {
            return;
        }

        $cache->deleteAll();
    }
}
