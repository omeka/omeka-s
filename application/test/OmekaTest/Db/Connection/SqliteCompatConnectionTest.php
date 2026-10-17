<?php
namespace OmekaTest\Db\Connection;

use Doctrine\DBAL\DriverManager;
use Omeka\Db\Connection\SqliteCompatConnection;
use Omeka\Db\Driver\SqliteDriver;
use Omeka\Test\TestCase;

class SqliteCompatConnectionTest extends TestCase
{
    protected $connection;

    public function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'driverClass' => SqliteDriver::class,
            'memory' => true,
            'wrapperClass' => SqliteCompatConnection::class,
        ]);
        $this->connection->exec('CREATE TABLE `setting` (`id` VARCHAR(190) PRIMARY KEY, `value` TEXT)');
    }

    public function testInsertIgnoreKeepsTheExistingRow()
    {
        // Modules built on Common run MySQL's INSERT IGNORE when installing.
        $sql = 'INSERT IGNORE INTO `setting` (`id`, `value`) VALUES (?, ?)';
        $this->connection->executeStatement($sql, ['foo', '"first"']);
        $this->connection->executeStatement($sql, ['foo', '"second"']);

        $this->assertSame('"first"', $this->connection->fetchOne('SELECT `value` FROM `setting` WHERE `id` = ?', ['foo']));
    }

    public function testOnDuplicateKeyUpdateUpsertsWithValuesAndParameters()
    {
        $sql = 'INSERT INTO `setting` (`id`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);';
        $this->connection->executeStatement($sql, ['foo', 'first']);
        $this->connection->executeStatement($sql, ['foo', 'second']);
        $this->assertSame([['id' => 'foo', 'value' => 'second']], $this->connection->fetchAllAssociative('SELECT * FROM setting'));
    }

    public function testOnDuplicateKeyUpdateKeepsExpressionsOnTheExistingRow()
    {
        // Page Hits, Advanced Search: counters, sometimes with a qualified target column.
        $this->connection->exec('CREATE TABLE hits (id INT PRIMARY KEY, label TEXT UNIQUE, hits INT NOT NULL DEFAULT 1)');
        foreach ([1, 2, 3] as $i) {
            $this->connection->exec("INSERT INTO hits (id, label) VALUES (7, 'home') ON DUPLICATE KEY UPDATE `hits`.`hits` = `hits`.`hits` + 1");
        }
        // Any unique key triggers the update, as in MySQL.
        $this->connection->executeStatement('INSERT INTO hits (id, label) VALUES (:id, :label) ON DUPLICATE KEY UPDATE hits = hits + 10', ['id' => 8, 'label' => 'home']);
        $this->assertSame([['id' => 7, 'label' => 'home', 'hits' => 13]], $this->connection->fetchAllAssociative('SELECT * FROM hits'));
    }

    public function testOnDuplicateKeyUpdateFromSelect()
    {
        $this->connection->exec("INSERT INTO setting VALUES ('foo', 'old')");
        $this->connection->exec('CREATE TABLE source (id TEXT, value TEXT)');
        $this->connection->exec("INSERT INTO source VALUES ('foo', 'new'), ('bar', 'added')");
        $this->connection->exec('INSERT INTO setting (id, value) SELECT s.id, s.value FROM source s JOIN source t ON t.id = s.id ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $this->assertSame(['bar' => 'added', 'foo' => 'new'], $this->connection->fetchAllKeyValue('SELECT id, value FROM setting ORDER BY id'));
    }

    public function testOnDuplicateKeyUpdateTextInStringsIsKept()
    {
        $this->connection->exec("INSERT INTO setting VALUES ('ON DUPLICATE KEY UPDATE', 'VALUES(x)')");
        $this->assertSame('VALUES(x)', $this->connection->fetchOne("SELECT value FROM setting WHERE id = 'ON DUPLICATE KEY UPDATE'"));
    }

    /** @dataProvider deleteJoinQueries */
    public function testDeleteWithJoinDeletesOnlyTargetRows($sql, $params, $expected)
    {
        $this->connection->exec('CREATE TABLE resource (id INT PRIMARY KEY)');
        $this->connection->exec('CREATE TABLE value (id INT PRIMARY KEY, resource_id INT, text TEXT)');
        $this->connection->exec('INSERT INTO resource VALUES (1)');
        $this->connection->exec("INSERT INTO value VALUES (1, 1, 'a'), (2, 1, 'a'), (3, 2, 'b'), (4, 1, 'c')");
        $this->connection->executeStatement($sql, $params);
        $this->assertSame($expected, array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM value ORDER BY id')));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM resource'));
    }

    public function deleteJoinQueries()
    {
        return [
            'orphans with left join' => ['DELETE `value` FROM `value` LEFT JOIN `resource` ON `resource`.`id` = `value`.`resource_id` WHERE `resource`.`id` IS NULL', [], [1, 2, 4]],
            'duplicates with self join and aliases' => ['DELETE v1 FROM `value` v1 JOIN `value` AS v2 ON v1.text = v2.text AND v1.resource_id = v2.resource_id WHERE v1.id > v2.id', [], [1, 3, 4]],
            'quoted alias with AS' => ['DELETE `t1` FROM `value` as `t1` INNER JOIN resource r ON r.id = t1.resource_id WHERE t1.text = ?;', ['c'], [1, 2, 3]],
            'single table with a parameter' => ['DELETE `value` FROM `value` WHERE `text` = :text', ['text' => 'a'], [3, 4]],
        ];
    }

    /** @dataProvider executionMethods */
    public function testCompoundStatementsKeepAllOperations($method)
    {
        $this->connection->$method('SET FOREIGN_KEY_CHECKS=0; DROP TABLE setting; SET FOREIGN_KEY_CHECKS=1');
        $this->assertSame([], $this->connection->getSchemaManager()->listTableNames());
        $this->assertSame(1, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
    }

    public function executionMethods()
    {
        return [['exec'], ['query'], ['executeQuery'], ['executeStatement']];
    }

    public function testCompoundSqlPreservesQuotedSeparatorsAndComments()
    {
        $this->connection->exec("-- install; NOW()\nINSERT INTO setting VALUES ('foo', 'a; b'); /* ; */ INSERT INTO setting VALUES ('bar', 'c; d')");
        $this->assertSame(['a; b', 'c; d'], $this->connection->fetchFirstColumn('SELECT value FROM setting ORDER BY id DESC'));
    }

    public function testPreparedQueriesAreTranslatedWithoutExecutingAtPrepareTime()
    {
        $statement = $this->connection->prepare('INSERT IGNORE INTO setting VALUES (?, NOW())');
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM setting'));
        $statement->execute(['foo']);
        $statement->execute(['foo']);
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM setting'));
        $this->assertNotNull($this->connection->fetchOne('SELECT value FROM setting'));
    }

    public function testPreparedCompoundQueriesDoNotExecuteLeadingStatements()
    {
        try {
            $this->connection->prepare("DROP TABLE setting; SELECT 1");
            $this->fail('Preparing multiple statements must fail before any are executed.');
        } catch (\RuntimeException $e) {
            $this->assertContains('setting', $this->connection->getSchemaManager()->listTableNames());
        }
    }

    public function testCompoundParametersAreRejectedBeforeAnyChanges()
    {
        $this->connection->exec("INSERT INTO setting VALUES ('existing', 'keep')");
        try {
            $this->connection->executeStatement('DELETE FROM setting; INSERT INTO setting VALUES (?, ?)', ['foo', 'bar']);
            $this->fail('Parameterized batches must not execute with unbound leading statements.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('parameters', $e->getMessage());
            $this->assertSame('keep', $this->connection->fetchOne('SELECT value FROM setting'));
        }
    }

    public function testShowTablesHonorsLikeAndFull()
    {
        $this->connection->exec('CREATE TABLE other (id INTEGER PRIMARY KEY AUTOINCREMENT)');
        $this->assertSame(['setting'], $this->connection->fetchFirstColumn("SHOW TABLES LIKE 'set%'"));
        $this->assertSame(['BASE TABLE'], array_column($this->connection->fetchAllAssociative("SHOW FULL TABLES LIKE 'other'"), 'Table_type'));
        $this->assertNotContains('sqlite_sequence', $this->connection->fetchFirstColumn('SHOW TABLES'));
    }

    /** @dataProvider columnQueries */
    public function testColumnMetadataHasMysqlFieldNames($sql)
    {
        $columns = $this->connection->fetchAllAssociative($sql);
        $this->assertSame('id', $columns[0]['Field']);
        $this->assertSame('PRI', $columns[0]['Key']);
        $this->assertSame('NO', $columns[0]['Null']);
        $this->assertSame('varchar(190)', strtolower($columns[0]['Type']));
        $this->assertArrayHasKey('Default', $columns[0]);
        $this->assertArrayHasKey('Extra', $columns[0]);
    }

    public function columnQueries()
    {
        return [['SHOW COLUMNS FROM setting'], ['DESCRIBE setting'], ['DESC setting']];
    }

    public function testShowColumnsHonorsLike()
    {
        $this->assertSame(['value'], $this->connection->fetchFirstColumn("SHOW COLUMNS FROM setting LIKE 'val%'"));
    }

    /** @dataProvider tableCollations */
    public function testCreateTableAcceptsQuotedMysqlTableCollation($collation)
    {
        $this->connection->exec("CREATE TABLE example (id INT AUTO_INCREMENT NOT NULL, note TEXT DEFAULT 'COLLATE `utf8mb4_unicode_ci`', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE $collation ENGINE = InnoDB");
        $this->connection->exec('INSERT INTO example DEFAULT VALUES');
        $this->assertSame('COLLATE `utf8mb4_unicode_ci`', $this->connection->fetchOne('SELECT note FROM example'));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT id FROM example'));
    }

    public function tableCollations()
    {
        return [['utf8mb4_unicode_ci'], ['`utf8mb4_unicode_ci`'], ['"utf8mb4_unicode_ci"'], ["'utf8mb4_unicode_ci'"]];
    }

    /** @dataProvider indexQueries */
    public function testShowIndexSupportsModuleFilters($sql)
    {
        $this->connection->exec('CREATE TABLE session (id INTEGER PRIMARY KEY, modified INT NOT NULL, token TEXT)');
        $this->connection->exec('CREATE INDEX idx_modified ON session (modified)');
        $rows = $this->connection->fetchAllAssociative($sql);
        $this->assertCount(1, $rows);
        $this->assertSame('session', $rows[0]['Table']);
        $this->assertSame('idx_modified', $rows[0]['Key_name']);
        $this->assertSame('modified', $rows[0]['Column_name']);
        $this->assertSame(1, (int) $rows[0]['Non_unique']);
        $this->assertSame(1, (int) $rows[0]['Seq_in_index']);
        $this->assertSame('', $rows[0]['Null']);
    }

    public function indexQueries()
    {
        return [
            ["SHOW INDEX FROM `session` WHERE `Key_name` = 'idx_modified'"],
            ["SHOW INDEX FROM `session` WHERE `Column_name` = 'modified'"],
            ['SHOW INDEX FROM `session` WHERE `column_name` = "modified";'],
            ["SHOW INDEXES IN session WHERE Column_name LIKE 'mod%' AND Non_unique = 1"],
            ["SHOW KEYS FROM session WHERE Key_name = 'idx_modified'"],
        ];
    }

    public function testShowIndexReportsCompositeUniqueIndexesInColumnOrder()
    {
        $this->connection->exec('CREATE UNIQUE INDEX idx_pair ON setting (value DESC, id)');
        $rows = $this->connection->fetchAllAssociative("SHOW INDEX FROM setting WHERE Key_name = 'idx_pair'");
        $this->assertSame(['value', 'id'], array_column($rows, 'Column_name'));
        $this->assertSame([1, 2], array_map('intval', array_column($rows, 'Seq_in_index')));
        $this->assertSame([0, 0], array_map('intval', array_column($rows, 'Non_unique')));
        $this->assertSame(['D', 'A'], array_column($rows, 'Collation'));
        $this->assertSame(['YES', ''], array_column($rows, 'Null'));
        $this->assertNull($rows[0]['Cardinality']);
        $this->assertNull($rows[0]['Sub_part']);
    }

    /** @dataProvider primaryIndexTables */
    public function testShowIndexIncludesPrimaryKeysWithoutDuplicates($ddl, $columns)
    {
        $this->connection->exec($ddl);
        $rows = $this->connection->fetchAllAssociative("SHOW INDEX FROM example WHERE Key_name = 'PRIMARY'");
        $this->assertSame($columns, array_column($rows, 'Column_name'));
        $this->assertSame(array_fill(0, count($columns), 0), array_map('intval', array_column($rows, 'Non_unique')));
    }

    public function primaryIndexTables()
    {
        return [
            ['CREATE TABLE example (id INTEGER PRIMARY KEY)', ['id']],
            ['CREATE TABLE example (id TEXT PRIMARY KEY)', ['id']],
            ['CREATE TABLE example (id INT, other INT, PRIMARY KEY(id, other))', ['id', 'other']],
            ['CREATE TABLE example (id INT, other INT, PRIMARY KEY(id, other)) WITHOUT ROWID', ['id', 'other']],
        ];
    }

    public function testPreparedShowIndexReadsCurrentMetadataAndAcceptsParameters()
    {
        $statement = $this->connection->prepare('SHOW INDEX FROM setting WHERE Key_name = ?');
        $this->connection->exec('CREATE INDEX new_index ON setting (value)');
        $statement->execute(['new_index']);
        $this->assertSame('value', $statement->fetchAssociative()['Column_name']);
        $this->assertFalse($this->connection->fetchOne('SHOW INDEX FROM setting WHERE Column_name = :column', ['column' => 'absent']));
    }

    public function testShowIndexReportsMysqlNamesOfIndexesDeclaredInCreateTable()
    {
        $this->connection->exec('CREATE TABLE example (value TEXT, KEY shared(value))');
        $rows = $this->connection->fetchAllAssociative("SHOW INDEX FROM example WHERE Key_name = 'shared'");
        $this->assertCount(1, $rows);
        $this->assertSame('shared', $rows[0]['Key_name']);
    }

    public function testShowIndexDoesNotReportAuxiliaryColumnsAsIndexed()
    {
        $this->connection->exec('CREATE INDEX expression_index ON setting (lower(value)) WHERE value IS NOT NULL');
        $rows = $this->connection->fetchAllAssociative("SHOW INDEX FROM setting WHERE Key_name = 'expression_index'");
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['Column_name']);
        $this->assertFalse($this->connection->fetchOne("SHOW INDEX FROM setting WHERE Column_name = 'value'"));
    }

    public function testShowIndexDistinguishesMissingTablesFromTablesWithoutIndexes()
    {
        $this->connection->exec('CREATE TABLE example (value TEXT)');
        $this->assertSame([], $this->connection->fetchAllAssociative('SHOW INDEX FROM example'));
        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->expectExceptionMessage('missing');
        $this->connection->fetchAllAssociative('SHOW INDEX FROM missing');
    }

    public function testCreateTablePreservesDefaultsAndQuotedIdentifiers()
    {
        $this->connection->exec("CREATE TABLE example (`int` VARCHAR(190) DEFAULT 'BIGINT, NOW(); AUTO_INCREMENT', number INT UNSIGNED, note TEXT COMMENT 'a,b')");
        $this->connection->exec('INSERT INTO example DEFAULT VALUES');
        $this->assertSame('BIGINT, NOW(); AUTO_INCREMENT', $this->connection->fetchOne('SELECT `int` FROM example'));
        $this->assertSame('INTEGER', $this->connection->fetchAllAssociative('PRAGMA table_info(example)')[1]['type']);
    }

    public function testNamedIndexesAreUniqueAcrossTablesAndCreationIsIdempotent()
    {
        foreach (['first', 'second', 'first'] as $table) {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS $table (id INT, value VARCHAR(190), KEY shared (value(10)), UNIQUE KEY unique_value (value))");
        }
        $this->connection->exec("INSERT INTO first VALUES (1, 'foo')");
        $this->connection->exec("INSERT INTO second VALUES (2, 'foo')");
        $this->assertCount(2, $this->connection->fetchAllAssociative('PRAGMA index_list(first)'));
        $this->assertCount(2, $this->connection->fetchAllAssociative('PRAGMA index_list(second)'));
    }

    /** @dataProvider unsupportedQueries */
    public function testUnsupportedSqlFailsExplicitly($sql)
    {
        $this->expectException(\RuntimeException::class);
        $this->connection->exec($sql);
    }

    public function unsupportedQueries()
    {
        return [
            ['SET arbitrary_variable=1'],
            ['ALTER TABLE setting ADD CONSTRAINT fk FOREIGN KEY (id) REFERENCES setting(id), ADD COLUMN extra TEXT'],
            ['CREATE TABLE example (text TEXT, FULLTEXT KEY search (text))'],
        ];
    }

    public function testForeignKeySwitchInTransactionFailsExplicitly()
    {
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->beginTransaction();
        try {
            $this->expectException(\RuntimeException::class);
            $this->connection->exec('SET FOREIGN_KEY_CHECKS=0');
        } finally {
            $this->connection->rollBack();
        }
    }

    public function testInlineForeignKeysRemainEnforced()
    {
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->exec('CREATE TABLE child (id INT PRIMARY KEY, parent_id VARCHAR(190), CONSTRAINT fk FOREIGN KEY (parent_id) REFERENCES setting(id) ON DELETE CASCADE)');
        $this->connection->executeStatement('INSERT INTO setting VALUES (?, ?)', ['foo', 'bar']);
        $this->connection->exec("INSERT INTO child VALUES (1, 'foo')");
        $this->connection->exec("DELETE FROM setting WHERE id='foo'");
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM child'));
        $this->assertSame([], $this->connection->fetchAllAssociative('PRAGMA foreign_key_check'));
    }

    /** @dataProvider alterForeignKeyQueries */
    public function testAlterTableAddsForeignKeysByRebuildingTheTable($sql, $expected)
    {
        $this->createForeignKeyFixture();
        $this->connection->exec($sql);

        $foreignKeys = [];
        foreach ($this->connection->fetchAllAssociative('PRAGMA foreign_key_list(child)') as $fk) {
            $foreignKeys[] = [$fk['table'], $fk['from'], $fk['to'], $fk['on_delete']];
        }
        sort($foreignKeys);
        $this->assertSame($expected, $foreignKeys);
        $this->assertSame(1, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
        $this->assertSame([], $this->connection->fetchAllAssociative('PRAGMA foreign_key_check'));
        // Rows, defaults, indexes and AUTOINCREMENT survive the rebuild.
        $this->assertSame([['id' => 7, 'setting_id' => 'foo', 'owner_id' => 1, 'label' => 'default']], $this->connection->fetchAllAssociative('SELECT id, setting_id, owner_id, label FROM child'));
        $this->assertEqualsCanonicalizing(['child_IDX_child_setting', 'child_UNIQ_child_label_owner'], $this->connection->fetchFirstColumn('SELECT name FROM pragma_index_list(\'child\')'));
        $this->assertStringContainsString('AUTOINCREMENT', $this->connection->fetchOne("SELECT sql FROM sqlite_master WHERE name='child'"));
        $this->connection->exec("DELETE FROM child; INSERT INTO child (setting_id, owner_id) VALUES ('foo', 1)");
        $this->assertSame(8, (int) $this->connection->fetchOne('SELECT id FROM child'));
    }

    public function alterForeignKeyQueries()
    {
        return [
            'unnamed' => [
                'ALTER TABLE child ADD FOREIGN KEY (setting_id) REFERENCES setting (id)',
                [['owner', 'owner_id', 'id', 'NO ACTION'], ['setting', 'setting_id', 'id', 'NO ACTION']],
            ],
            'named doctrine' => [
                'ALTER TABLE `child` ADD CONSTRAINT `FK_22B35429EE35BD72` FOREIGN KEY (`setting_id`) REFERENCES `setting` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;',
                [['owner', 'owner_id', 'id', 'NO ACTION'], ['setting', 'setting_id', 'id', 'CASCADE']],
            ],
            'several in one statement' => [
                'ALTER TABLE child ADD CONSTRAINT FK_1 FOREIGN KEY (setting_id) REFERENCES setting (id) ON DELETE SET NULL, ADD CONSTRAINT "FK_2" FOREIGN KEY ("owner_id", "label") REFERENCES "owner" ("id", "name") ON DELETE CASCADE',
                [['owner', 'label', 'name', 'CASCADE'], ['owner', 'owner_id', 'id', 'CASCADE'], ['owner', 'owner_id', 'id', 'NO ACTION'], ['setting', 'setting_id', 'id', 'SET NULL']],
            ],
        ];
    }

    public function testAlterTableForeignKeyCascadesAndDoesNotCascadeDuringRebuild()
    {
        $this->createForeignKeyFixture();
        // A grandchild that cascades from child must not lose rows when child is rebuilt.
        $this->connection->exec('CREATE TABLE grandchild (id INT PRIMARY KEY, child_id INT, CONSTRAINT fk_gc FOREIGN KEY (child_id) REFERENCES child (id) ON DELETE CASCADE)');
        $this->connection->exec('INSERT INTO grandchild VALUES (1, 7)');

        $this->connection->exec('ALTER TABLE child ADD CONSTRAINT FK_child_setting FOREIGN KEY (setting_id) REFERENCES setting (id) ON DELETE CASCADE');

        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM grandchild'));
        $this->connection->exec("DELETE FROM setting WHERE id = 'foo'");
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM child'));
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM grandchild'));
    }

    /** @dataProvider executionMethods */
    public function testInstallScriptsCreateTablesBeforeAddingForeignKeys($method)
    {
        // Doctrine install scripts create every table, then add the foreign keys.
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->$method("CREATE TABLE child (id INT AUTO_INCREMENT NOT NULL, setting_id VARCHAR(190) DEFAULT NULL, INDEX IDX_child (setting_id), PRIMARY KEY(id));\nALTER TABLE child ADD CONSTRAINT FK_child FOREIGN KEY (setting_id) REFERENCES setting (id) ON DELETE CASCADE;\n");
        $this->assertCount(1, $this->connection->fetchAllAssociative('PRAGMA foreign_key_list(child)'));
        $this->assertSame(1, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
    }

    public function testAlterTableForeignKeyKeepsDisabledEnforcement()
    {
        $this->createForeignKeyFixture();
        $this->connection->exec('PRAGMA foreign_keys = OFF');
        $this->connection->exec('ALTER TABLE child ADD FOREIGN KEY (setting_id) REFERENCES setting (id)');
        $this->assertSame(0, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
        $this->assertCount(2, $this->connection->fetchAllAssociative('PRAGMA foreign_key_list(child)'));
    }

    public function testAlterTableForeignKeyInTransactionWithEnforcementFailsExplicitly()
    {
        $this->createForeignKeyFixture();
        $this->connection->beginTransaction();
        try {
            $this->connection->exec('ALTER TABLE child ADD FOREIGN KEY (setting_id) REFERENCES setting (id)');
            $this->fail('Rebuilding a table with enforced foreign keys inside a transaction could cascade.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('transaction', $e->getMessage());
        } finally {
            $this->connection->rollBack();
        }
        $this->assertCount(1, $this->connection->fetchAllAssociative('PRAGMA foreign_key_list(child)'));
    }

    public function testAlterTableDropsForeignKeyByRebuildingTheTable()
    {
        $this->createForeignKeyFixture();
        $this->connection->exec('ALTER TABLE child ADD CONSTRAINT `FK_child_setting` FOREIGN KEY (setting_id) REFERENCES setting (id) ON DELETE CASCADE');
        $this->connection->exec('ALTER TABLE `child` DROP FOREIGN KEY `FK_child_setting`;');

        $this->assertSame(['owner'], $this->connection->fetchFirstColumn("SELECT \"table\" FROM pragma_foreign_key_list('child')"));
        $this->assertSame(1, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
        $this->assertEqualsCanonicalizing(['child_IDX_child_setting', 'child_UNIQ_child_label_owner'], $this->connection->fetchFirstColumn('SELECT name FROM pragma_index_list(\'child\')'));
        // The dropped key no longer cascades.
        $this->connection->exec("DELETE FROM setting WHERE id = 'foo'");
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM child'));
    }

    public function testUninstallScriptsDropForeignKeysBeforeTables()
    {
        // Doctrine uninstall scripts drop the foreign keys, then the tables.
        $this->createForeignKeyFixture();
        $this->connection->exec('ALTER TABLE child DROP FOREIGN KEY fk_owner; DROP TABLE child; DROP TABLE owner;');
        $this->assertNotContains('child', $this->connection->getSchemaManager()->listTableNames());
        $this->assertSame(1, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
    }

    public function testAlterTableReplacesForeignKeyInOneStatement()
    {
        $this->createForeignKeyFixture();
        $this->connection->exec('ALTER TABLE child DROP FOREIGN KEY fk_owner, ADD CONSTRAINT fk_owner FOREIGN KEY (owner_id) REFERENCES owner (id) ON DELETE CASCADE');
        $this->assertSame(['CASCADE'], $this->connection->fetchFirstColumn("SELECT on_delete FROM pragma_foreign_key_list('child')"));
    }

    public function testAlterTableDropUnknownForeignKeyFailsExplicitly()
    {
        $this->createForeignKeyFixture();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FK_missing');
        $this->connection->exec('ALTER TABLE child DROP FOREIGN KEY FK_missing');
    }

    /** @dataProvider alterIndexQueries */
    public function testAlterTableAddsAndDropsIndexes($sql, $expected)
    {
        $this->createForeignKeyFixture();
        $this->connection->exec($sql);
        $indexes = [];
        foreach ($this->connection->fetchAllAssociative("SELECT name, \"unique\" FROM pragma_index_list('child') WHERE origin = 'c'") as $index) {
            $indexes[$index['name']] = implode(',', $this->connection->fetchFirstColumn("SELECT coalesce(name, 'expr') FROM pragma_index_info('{$index['name']}')")) . ($index['unique'] ? ' unique' : '');
        }
        ksort($indexes);
        ksort($expected);
        $this->assertSame($expected, $indexes);
    }

    public function alterIndexQueries()
    {
        $base = ['child_IDX_child_setting' => 'setting_id', 'child_UNIQ_child_label_owner' => 'label,owner_id unique'];
        return [
            'add doctrine' => [
                'ALTER TABLE child ADD INDEX IDX_child_owner (owner_id)',
                $base + ['child_IDX_child_owner' => 'owner_id'],
            ],
            'add several quoted, unique, prefix, online options' => [
                'ALTER TABLE `child` ADD UNIQUE INDEX `UNIQ_owner_label` (`owner_id`, `label`), ADD KEY `label` (`label`(10)), ALGORITHM=INPLACE, LOCK=NONE;',
                $base + ['child_UNIQ_owner_label' => 'owner_id,label unique', 'child_label' => 'label'],
            ],
            'drop index created with the table' => [
                'ALTER TABLE child DROP INDEX IDX_child_setting',
                ['child_UNIQ_child_label_owner' => 'label,owner_id unique'],
            ],
            'drop and add in one statement, if exists' => [
                'ALTER TABLE child DROP KEY IF EXISTS `missing`, DROP INDEX UNIQ_child_label_owner, ADD UNIQUE INDEX IF NOT EXISTS UNIQ_label (label)',
                ['child_IDX_child_setting' => 'setting_id', 'child_UNIQ_label' => 'label unique'],
            ],
        ];
    }

    public function testShowIndexReportsMysqlNamesOfAddedIndexes()
    {
        // Modules such as Common check SHOW INDEX by name before adding an index.
        $this->createForeignKeyFixture();
        $this->connection->exec('ALTER TABLE child ADD INDEX idx_label (label)');
        $keyNames = function (string $where): array {
            return array_column($this->connection->fetchAllAssociative("SHOW INDEX FROM child WHERE $where"), 'Key_name');
        };
        $this->assertSame(['idx_label'], $keyNames("Key_name = 'idx_label'"));
        $this->assertSame(['IDX_child_setting'], $keyNames("Column_name = 'setting_id'"));
        // An index named after its column keeps its name, e.g. media_type on media.
        $this->connection->exec('ALTER TABLE child ADD COLUMN child_type TEXT');
        $this->connection->exec('CREATE INDEX child_type ON child (child_type)');
        $this->assertSame(['child_type'], $keyNames("Column_name = 'child_type'"));
    }

    public function testAlterTableIndexNamesArePerTable()
    {
        // MySQL index names are per table; several modules reuse names such as "is_public".
        $this->createForeignKeyFixture();
        $this->connection->exec('ALTER TABLE child ADD INDEX is_public (label)');
        $this->connection->exec('ALTER TABLE owner ADD INDEX is_public (name)');
        $this->assertCount(2, $this->connection->fetchFirstColumn("SELECT name FROM sqlite_master WHERE type = 'index' AND name LIKE '%is_public'"));
    }

    public function testAlterTableDropsIndexCreatedWithoutTablePrefix()
    {
        $this->createForeignKeyFixture();
        $this->connection->exec('CREATE INDEX idx_plain ON child (label)');
        $this->connection->exec('ALTER TABLE child DROP INDEX idx_plain');
        $this->assertFalse($this->connection->fetchOne("SELECT name FROM sqlite_master WHERE name = 'idx_plain'"));
    }

    public function testAlterTableDropUnknownIndexFailsExplicitly()
    {
        $this->createForeignKeyFixture();
        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->connection->exec('ALTER TABLE child DROP INDEX idx_missing');
    }

    private function createForeignKeyFixture(): void
    {
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->exec('CREATE TABLE owner (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(190) DEFAULT NULL, UNIQUE INDEX UNIQ_owner (id, name), PRIMARY KEY(id))');
        $this->connection->exec('CREATE TABLE child (id INT AUTO_INCREMENT NOT NULL, setting_id VARCHAR(190) DEFAULT NULL, owner_id INT NOT NULL, label VARCHAR(190) DEFAULT \'default\' NOT NULL, INDEX IDX_child_setting (setting_id), UNIQUE INDEX UNIQ_child_label_owner (label, owner_id), PRIMARY KEY(id), CONSTRAINT fk_owner FOREIGN KEY (owner_id) REFERENCES owner (id))');
        $this->connection->exec("INSERT INTO setting VALUES ('foo', 'bar')");
        $this->connection->exec("INSERT INTO owner (id, name) VALUES (1, 'default')");
        $this->connection->exec("INSERT INTO child (id, setting_id, owner_id) VALUES (7, 'foo', 1)");
    }

    public function testCoreSchemaInstallsWithForeignKeys()
    {
        $this->connection->exec('DROP TABLE setting');
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->exec(file_get_contents(OMEKA_PATH . '/application/data/install/schema.sqlite.sql'));
        $this->assertContains('resource', $this->connection->getSchemaManager()->listTableNames());
        $this->assertNotEmpty($this->connection->fetchAllAssociative('PRAGMA foreign_key_list(media)'));
        $this->assertSame([], $this->connection->fetchAllAssociative('PRAGMA foreign_key_check'));
    }

    public function testTriggerBodiesAreNotSplit()
    {
        $this->connection->exec("CREATE TRIGGER change_setting AFTER INSERT ON setting BEGIN UPDATE setting SET value='changed; value' WHERE id=NEW.id; END;");
        $this->connection->exec("INSERT INTO setting VALUES ('foo', 'bar')");
        $this->assertSame('changed; value', $this->connection->fetchOne('SELECT value FROM setting'));
    }

    public function testAutoIncrementDoesNotReuseDeletedIdsAndTruncateResetsIt()
    {
        $this->connection->exec('CREATE TABLE example (id INT UNSIGNED NOT NULL AUTO_INCREMENT, value TEXT, PRIMARY KEY(id)) ENGINE=InnoDB');
        $this->connection->exec("INSERT INTO example (value) VALUES ('first')");
        $this->connection->exec('DELETE FROM example');
        $this->connection->exec("INSERT INTO example (value) VALUES ('second')");
        $this->assertSame(2, (int) $this->connection->fetchOne('SELECT id FROM example'));
        $this->connection->exec('TRUNCATE TABLE example');
        $this->connection->exec("INSERT INTO example (value) VALUES ('third')");
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT id FROM example'));
    }

    public function testNativeTableOptionsArePreserved()
    {
        $this->connection->exec('CREATE TABLE native (id INTEGER PRIMARY KEY) WITHOUT ROWID');
        $this->assertStringContainsString('WITHOUT ROWID', $this->connection->fetchOne("SELECT sql FROM sqlite_master WHERE name='native'"));
    }

    public function testTableCommentDoesNotChangeBodyBoundaries()
    {
        $this->connection->exec("CREATE TABLE example (id INT PRIMARY KEY, value TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Example (a,b)'");
        $this->assertCount(2, $this->connection->fetchAllAssociative('PRAGMA table_info(example)'));
    }

    public function testMysqlFunctionsPreserveNullsAndLiteralText()
    {
        $this->assertNull($this->connection->fetchOne('SELECT UNIX_TIMESTAMP(NULL)'));
        $this->assertGreaterThan(0, (int) $this->connection->fetchOne('SELECT UNIX_TIMESTAMP()'));
        $this->assertNull($this->connection->fetchOne("SELECT CONCAT('foo', NULL)"));
        $this->assertSame('foo|bar', $this->connection->fetchOne("SELECT CONCAT_WS('|', 'foo', NULL, 'bar')"));
        $this->assertSame('NOW(); CURDATE()', $this->connection->fetchOne("SELECT 'NOW(); CURDATE()'"));
    }

    /** @dataProvider moduleFunctionQueries */
    public function testMysqlFunctionsUsedByModules($sql, $expected)
    {
        $this->assertSame($expected, $this->connection->fetchOne($sql));
    }

    public function moduleFunctionQueries()
    {
        return [
            'substring_index left' => ["SELECT SUBSTRING_INDEX('a.b.c', '.', 2)", 'a.b'],
            'substring_index right' => ["SELECT SUBSTRING_INDEX('a.b.c', '.', -1)", 'c'],
            'substring_index beyond' => ["SELECT SUBSTRING_INDEX('a.b.c', '.', 5)", 'a.b.c'],
            'substring_index zero' => ["SELECT SUBSTRING_INDEX('a.b.c', '.', 0)", ''],
            'substring_index null' => ["SELECT SUBSTRING_INDEX(NULL, '.', 1)", null],
            'year' => ["SELECT YEAR('2024-02-29 13:45:06')", 2024],
            'month' => ["SELECT MONTH('2024-02-29')", 2],
            'day' => ["SELECT DAY('2024-02-29')", 29],
            'hour minute second' => ["SELECT HOUR('2024-02-29 13:45:06') || ':' || MINUTE('2024-02-29 13:45:06') || ':' || SECOND('2024-02-29 13:45:06')", '13:45:6'],
            'year invalid' => ["SELECT YEAR('not a date')", null],
            'last_day' => ["SELECT LAST_DAY('2024-02-10 08:00:00')", '2024-02-29'],
            'str_to_date date' => ["SELECT STR_TO_DATE('29/02/2024', '%d/%m/%Y')", '2024-02-29'],
            'str_to_date datetime' => ["SELECT STR_TO_DATE('2024-02-29 13:45', '%Y-%m-%d %H:%i')", '2024-02-29 13:45:00'],
            'str_to_date invalid' => ["SELECT STR_TO_DATE('foo', '%Y-%m-%d')", null],
            'regexp_replace' => ["SELECT REGEXP_REPLACE('Omeka S 4', '[0-9]+', 'x')", 'Omeka S x'],
            'regexp_replace backreference, case-insensitive' => ["SELECT REGEXP_REPLACE('abc ABC', '(b)', '[$1]')", 'a[b]c A[B]C'],
            'regexp_substr' => ["SELECT REGEXP_SUBSTR('POINT(1 2)', '[0-9]+ [0-9]+')", '1 2'],
            'regexp_substr none' => ["SELECT REGEXP_SUBSTR('abc', '[0-9]')", null],
            'regexp operator case-insensitive' => ["SELECT 'Omeka' REGEXP '^omeka$'", 1],
            'crc32' => ["SELECT CRC32('omeka') = 3870085686", 1],
            'sha1' => ["SELECT SHA1('omeka')", sha1('omeka')],
            'sha2' => ["SELECT SHA2('omeka', 256)", hash('sha256', 'omeka')],
            'json_unquote string' => ['SELECT JSON_UNQUOTE(\'"a\\"b"\')', 'a"b'],
            'json_unquote other' => ["SELECT JSON_UNQUOTE('[1]')", '[1]'],
        ];
    }

    public function testRandReturnsAFractionAndSortsRows()
    {
        $value = (float) $this->connection->fetchOne('SELECT RAND()');
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThan(1, $value);
        $this->connection->exec("INSERT INTO setting VALUES ('a', '1'), ('b', '2')");
        $this->assertCount(2, $this->connection->fetchFirstColumn('SELECT id FROM setting ORDER BY RAND()'));
    }

    public function testUniquePrefixIndexesEnforcePrefixUniqueness()
    {
        $this->connection->exec('CREATE TABLE example (value TEXT, UNIQUE KEY prefix (value(3)))');
        $this->connection->exec("INSERT INTO example VALUES ('abcd')");
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->connection->exec("INSERT INTO example VALUES ('abce')");
    }
}
