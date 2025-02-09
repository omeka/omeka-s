<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;
use Omeka\Installation\Installer;

/**
 * Install schema task.
 */
class InstallSchemaTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $schemaPath = OMEKA_PATH . '/application/data/install/schema.sql';
        if (!is_readable($schemaPath)) {
            $installer->addError('Could not read the schema installation file.');
            return;
        }

        $schema = file_get_contents($schemaPath);
        $statements = explode(';', $schema);
        $connection = $installer->getServiceLocator()->get('Omeka\Connection');
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ('' === $statement) {
                    continue;
                }
                $connection->exec($statement);
            }
        } catch (DBALException $e) {
            $installer->addError($e->getMessage());
            return;
        }
    }
}
