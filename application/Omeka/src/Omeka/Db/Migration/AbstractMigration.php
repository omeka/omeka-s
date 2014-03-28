<?php
namespace Omeka\Db\Migration;

use Doctrine\DBAL\Connection;
use Omeka\Db\Helper as DbHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract migration class.
 *
 * Most migrations should extend from this class.
 */
abstract class AbstractMigration implements MigrationInterface
{
    /**
     * @var DbHelper
     */
    protected $dbHelper;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Default downgrade.
     *
     * By default, downgrade is unsupported and simply throws an exception.
     */
    public function down()
    {
        throw new Exception\DowngradeUnsupportedException('This migration cannot be downgraded.');
    }

    /**
     * Get the db helper
     *
     * @return DbHelper
     */
    protected function getDbHelper()
    {
        if (null === $this->dbHelper) {
            $this->dbHelper = $this->getServiceLocator()
                ->get('Omeka\DbHelper');
        }
        return $this->dbHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
