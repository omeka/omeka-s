<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Job\Dispatcher as JobDispatcher;

class SeparateRecordAndTextForFullText implements ConstructedMigrationInterface
{
    /**
     * @var \Omeka\Job\Dispatcher
     */
    private $jobDispatcher;

    public function __construct(JobDispatcher $jobDispatcher)
    {
        $this->jobDispatcher = $jobDispatcher;
    }

    public function up(Connection $conn)
    {
        $sql = <<<'SQL'
TRUNCATE TABLE `fulltext_search`;

ALTER TABLE `fulltext_search`
ADD `record` longtext COLLATE 'utf8mb4_unicode_ci' NULL AFTER `title`;

ALTER TABLE `fulltext_search`
ADD FULLTEXT `IDX_AA31FE4A2B36786B9B349F91` (`title`, `record`),
ADD FULLTEXT `IDX_AA31FE4A3B8BA7C7` (`text`),
DROP INDEX `IDX_AA31FE4A2B36786B3B8BA7C7`;

SQL;
        $conn->executeStatement($sql);

        $this->jobDispatcher->dispatch(\DerivativeMedia\Job\DerivativeItem::class);
    }

    public static function create(ServiceLocatorInterface $services)
    {
        return new self($services->get(\Omeka\Job\Dispatcher::class));
    }
}
