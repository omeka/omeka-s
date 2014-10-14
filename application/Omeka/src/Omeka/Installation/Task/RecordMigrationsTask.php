<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;
use Omeka\Installation\Manager;

/**
 * Task to initialize the migrations table with all existing migrations.
 */
class RecordMigrationsTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
        $migrator = $manager->getServiceLocator()->get('Omeka\MigrationManager');
        $conn = $manager->getServiceLocator()->get('Omeka\Connection');

        $migrations = $migrator->getAvailableMigrations();

        $conn->beginTransaction();
        foreach ($migrations as $version => $data) {
            $migrator->recordMigration($version);
        }
        $conn->commit();
    }
}
