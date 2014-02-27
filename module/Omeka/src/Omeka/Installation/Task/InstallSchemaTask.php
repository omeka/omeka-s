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
        $conn = $this->getServiceLocator()->get('EntityManager')->getConnection();
        $appConfig = $this->getServiceLocator()->get('ApplicationConfig');
        
        // Check whether the database was already installed by checking whether
        // the resource table exists.
        $tables = $conn->getSchemaManager()->listTableNames();
        $checkTable = $appConfig['entity_manager']['table_prefix'] . 'resource';
        if (in_array($checkTable, $tables)) {
            $this->addError('Omeka is already installed.');
            return;
        }

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
                $appConfig['entity_manager']['table_prefix'],
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
