<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;
use Omeka\Installation\Installer;
use Omeka\Service\ConnectionFactory;

/**
 * Install schema task.
 */
class InstallSchemaTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $connection = $installer->getServiceLocator()->get('Omeka\Connection');
        $isSqlite = ConnectionFactory::isSqlite($connection);

        $schemaFile = $isSqlite ? 'schema.sqlite.sql' : 'schema.sql';
        $schemaPath = OMEKA_PATH . '/application/data/install/' . $schemaFile;
        if (!is_readable($schemaPath)) {
            $installer->addError('Could not read the schema installation file.');
            return;
        }

        $schema = file_get_contents($schemaPath);
        $statements = explode(';', $schema);
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
