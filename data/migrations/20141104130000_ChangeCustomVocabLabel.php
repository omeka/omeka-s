<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ChangeCustomVocabLabel extends AbstractMigration
{
    public function up()
    {
        $dql = 'UPDATE Omeka\Model\Entity\Vocabulary v
        SET v.label = :newLabel
        WHERE v.prefix = :prefix';
        $this->getDbHelper()->getEntityManager()
            ->createQuery($dql)
            ->setParameters(array(
                'newLabel' => 'Custom Vocabulary',
                'prefix' => 'omeka',
            ))
            ->execute();
    }
}
