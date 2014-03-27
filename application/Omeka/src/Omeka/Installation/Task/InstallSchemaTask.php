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
        $dbHelper = $this->getServiceLocator()->get('Omeka\DbHelper');

        $schemaPath = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schemaPath)) {
            $this->addError('Could not read the schema installation file.');
            return;
        }

        try {
            $dbHelper->executeQueries(file_get_contents($schemaPath));
        } catch (DBALException $e) {
            $this->addError($e->getMessage());
            return;
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
