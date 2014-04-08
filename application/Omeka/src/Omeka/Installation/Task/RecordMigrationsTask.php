<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;

/**
 * Task to initialize the migrations table with all existing migrations.
 */
class RecordMigrationsTask extends AbstractTask
{
    /**
     * Record all migrations.
     */
    public function perform()
    {
        $sl = $this->getServiceLocator();
        $migrator = $sl->get('Omeka\MigrationManager');
        $conn = $sl->get('Omeka\Connection');

        $migrations = $migrator->getAvailableMigrations();

        $conn->beginTransaction();
        foreach ($migrations as $version => $data) {
            $migrator->recordMigration($version);
        }
        $conn->commit();

        $this->addInfo(
            $this->getTranslator()->translate('Successfully recorded all migrations.')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTranslator()->translate('Record initial migrations.');
    }
}
