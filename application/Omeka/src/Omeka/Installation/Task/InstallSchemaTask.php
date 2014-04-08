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
        $t = $this->getTranslator();
        $dbHelper = $this->getServiceLocator()->get('Omeka\DbHelper');

        $schemaPath = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schemaPath)) {
            $this->addError(
                $t->translate('Could not read the schema installation file.')
            );
            return;
        }

        try {
            $dbHelper->execute(file_get_contents($schemaPath));
        } catch (DBALException $e) {
            $this->addError($e->getMessage());
            return;
        }

        $this->addInfo(
            $t->translate('Successfully installed the Omeka database.')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTranslator()->translate('Install the Omeka database');
    }
}
