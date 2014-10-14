<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;
use Omeka\Installation\Manager;

/**
 * Install schema task.
 */
class InstallSchemaTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
        $dbHelper = $manager->getServiceLocator()->get('Omeka\DbHelper');

        $schemaPath = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schemaPath)) {
            $manager->addError('Could not read the schema installation file.');
            return;
        }

        try {
            $dbHelper->execute(file_get_contents($schemaPath));
        } catch (DBALException $e) {
            $manager->addError($e->getMessage());
            return;
        }
    }
}
