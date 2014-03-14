<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;

/**
 * Install schema task.
 */
class InstallSchemaTask extends AbstractTask
{
    /**
     * Install the Omeka database.
     */
    public function perform()
    {
        $conn = $this->getServiceLocator()->get('Omeka\Connection');
        $config = $this->getServiceLocator()->get('ApplicationConfig');

        $schemaPath = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schemaPath)) {
            $this->addError('Could not read the schema installation file.');
            return;
        }

        $schema = file_get_contents($schemaPath);
        $statements = explode(';', $schema);
        
        // The schema file uses "DBPREFIX_" as a placeholder table prefix.
        // Replace them here with the configured table prefix.
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!$statement) {
                continue;
            }
            $prefixedStatement = str_replace(
                'DBPREFIX_',
                $config['connection']['table_prefix'],
                $statement
            );
            try {
                $conn->executeQuery($prefixedStatement);
            } catch (DBALException $e) {
                $this->addError($e->getMessage());
                return;
            }
        }
        $this->addInfo('Successfully installed the Omeka database.');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Install the Omeka database';
    }
}
