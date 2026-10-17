<?php
namespace OmekaTest\Db\Connection;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Laminas\EventManager\Event;
use Omeka\Api\Adapter\ItemAdapter;
use Omeka\Api\Request;
use Omeka\Db\Connection\SqliteCompatConnection;
use Omeka\Db\Driver\SqliteDriver;
use Omeka\Test\TestCase;

class SqliteQueryTest extends TestCase
{
    protected $entityManager;

    public function setUp(): void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'driverClass' => SqliteDriver::class,
            'memory' => true,
            'wrapperClass' => SqliteCompatConnection::class,
        ]);
        $config = Setup::createAnnotationMetadataConfiguration([OMEKA_PATH . '/application/src/Entity'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy);
        $functions = (require OMEKA_PATH . '/application/config/module.config.php')['entity_manager']['functions'];
        $config->setCustomStringFunctions($functions['string']);
        $this->entityManager = EntityManager::create($connection, $config);
        $connection->exec('CREATE TABLE setting (id VARCHAR(190) PRIMARY KEY, value TEXT)');
        $connection->exec("INSERT INTO setting VALUES ('1', 'one'), ('2', 'two')");
    }

    public function testGroupConcatCombinesMultipleFieldsPerRow()
    {
        $value = $this->entityManager->createQuery("SELECT GROUP_CONCAT(s.id, s.id ORDER BY s.id SEPARATOR '|') FROM Omeka\\Entity\\Setting s")->getSingleScalarResult();
        $this->assertSame('11|22', $value);
    }

    public function testDistinctGroupConcatDoesNotSilentlyDiscardSeparator()
    {
        $this->expectException(\RuntimeException::class);
        $this->entityManager->createQuery("SELECT GROUP_CONCAT(DISTINCT s.id SEPARATOR '|') FROM Omeka\\Entity\\Setting s")->getSingleScalarResult();
    }

    public function testFulltextDefaultSortExecutesOnSqlite()
    {
        $this->entityManager->getConnection()->exec('CREATE TABLE fulltext_search (id INTEGER, resource TEXT, owner_id INTEGER, is_public INTEGER, title TEXT, text TEXT)');
        $this->entityManager->getConnection()->exec("INSERT INTO fulltext_search VALUES (1, 'items', NULL, 1, 'match', 'text'), (2, 'items', NULL, 1, 'match', 'text')");
        $qb = $this->fulltextQuery();
        $this->assertSame(['2', '1'], array_column($qb->getQuery()->getScalarResult(), 'id'));
    }

    public function testFulltextTreatsWildcardsAsLiteralText()
    {
        $this->entityManager->getConnection()->exec('CREATE TABLE fulltext_search (id INTEGER, resource TEXT, owner_id INTEGER, is_public INTEGER, title TEXT, text TEXT)');
        $this->entityManager->getConnection()->exec("INSERT INTO fulltext_search VALUES (1, 'items', NULL, 1, '100%_!', 'text'), (2, 'items', NULL, 1, 'match', 'text')");
        $this->assertSame(['1'], array_column($this->fulltextQuery('%_!')->getQuery()->getScalarResult(), 'id'));
    }

    public function testFulltextKeepsMysqlNaturalLanguageSearch()
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true, 'platform' => new MySqlPlatform]);
        $this->entityManager = EntityManager::create($connection, $this->entityManager->getConfiguration());
        $sql = $this->fulltextQuery()->getQuery()->getSQL();
        $this->assertStringContainsString('AGAINST', $sql);
        $this->assertStringNotContainsString('BOOLEAN MODE', $sql);
        $this->assertStringContainsString('> 0', $sql);
    }

    private function fulltextQuery($search = 'match')
    {
        require_once OMEKA_PATH . '/application/Module.php';
        $acl = $this->createMock(\Omeka\Permissions\Acl::class);
        $acl->method('userIsAllowed')->willReturn(true);
        $module = new \Omeka\Module;
        $module->setServiceLocator($this->getServiceManager([
            'Omeka\Connection' => $this->entityManager->getConnection(),
            'Omeka\Acl' => $acl,
        ]));
        $qb = $this->entityManager->createQueryBuilder()->select('omeka_root.id')->from('Omeka\Entity\Setting', 'omeka_root');
        $request = new Request(Request::SEARCH, 'items');
        $request->setContent(['fulltext_search' => $search, 'sort_order' => 'desc', 'sort_order_default' => true]);
        $event = new Event('api.search.query', new ItemAdapter, ['request' => $request, 'queryBuilder' => $qb]);
        $module->searchFulltext($event);
        $event->setName('api.search.query.finalize');
        $module->searchFulltext($event);
        return $qb;
    }
}
