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
        $config = $this->getServiceLocator()->get('Config');
        
        // Check if tables already exist
        // @todo filter for only the proper prefixes in the table names
        $tables = $conn->getSchemaManager()->listTableNames();
        if (!empty($tables)) {
            $this->addError('Omeka is already installed.');
            return;
        }

        $schema = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schema)) {
            $this->addError('Could not read the schema installation file.');
            return;
        }

        $statements = file($schema);
        if (!is_array($statements)) {
            $this->addError('Could not read the schema installation file.');
            return;
        }
        
        // The schema file uses "DBPREFIX_" as a placeholder table prefix.
        // Replace them here with the configured table prefix.
        foreach ($statements as $statement) {
            $prefixedStatement = str_replace(
                'DBPREFIX_',
                $config['entity_manager']['table_prefix'],
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
