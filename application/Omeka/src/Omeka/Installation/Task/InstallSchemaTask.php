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
        $schemaPath = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schemaPath)) {
            $manager->addError('Could not read the schema installation file.');
            return;
        }

        $schema = file_get_contents($schemaPath);
        $statements = explode(';', $schema);
        $connection = $manager->getServiceLocator()->get('Omeka\Connection');
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ('' === $statement) {
                    continue;
                }
                $connection->exec($statement);
            }
        } catch (DBALException $e) {
            $manager->addError($e->getMessage());
            return;
        }
    }
}
