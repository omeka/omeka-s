<?php
namespace Omeka\Db\Migration;

use GlobIterator;
use PDO;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Migration manager.
 */
class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

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
            $migration->up();
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
        $this->getServiceLocator()->get('Omeka\Connection')
            ->insert('migration', array('version' => $version));
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

        $migration = new $class;
        $migration->setServiceLocator($this->getServiceLocator());
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
        $completed = $this->getServiceLocator()->get('Omeka\Connection')
            ->executeQuery("SELECT version FROM migration")
            ->fetchAll(PDO::FETCH_COLUMN);
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
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getServiceLocator()->get('MvcTranslator');
        }
        return $this->translator;
    }
}
