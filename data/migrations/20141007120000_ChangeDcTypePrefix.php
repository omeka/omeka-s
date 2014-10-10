<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ChangeDcTypePrefix extends AbstractMigration
{
    public function up()
    {
        $dql = 'UPDATE Omeka\Model\Entity\Vocabulary v
        SET v.prefix = :newPrefix
        WHERE v.prefix = :oldPrefix';
        $this->getDbHelper()->getEntityManager()
            ->createQuery($dql)
            ->setParameters(array(
                'newPrefix' => 'dctype',
                'oldPrefix' => 'dcmitype',
            ))
            ->execute();
    }
}
