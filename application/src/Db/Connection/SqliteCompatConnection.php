<?php
namespace Omeka\Db\Connection;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * A DBAL Connection wrapper that transparently translates MySQL-specific SQL
 * to SQLite equivalents. This allows third-party modules that use MySQL-only
 * syntax (SHOW TABLES, SET FOREIGN_KEY_CHECKS, etc.) to work with SQLite.
 */
class SqliteCompatConnection extends Connection
{
    private const TRANSLATABLE_KEYWORDS = ['SHOW', 'SET', 'TRUNCATE', 'DESCRIBE', 'DESC', 'CREATE', 'ALTER'];

    private const SQL_PROTECTED = <<<'REGEX'
'(?:[^']|'')*'|"(?:[^"]|"")*"|`(?:[^`]|``)*`|\[[^\]]*\]|--[^\r\n]*|\/\*.*?\*\/
REGEX;

    public function connect()
    {
        $newConnection = parent::connect();
        if ($newConnection) {
            $this->registerMysqlFunctions();
        }
        return $newConnection;
    }

    public function exec($sql): int
    {
        // Translate each statement only after the previous ones ran, as some
        // translations read the current schema (e.g. foreign key rebuilds).
        $result = 0;
        foreach ($this->splitStatements($sql) as $part) {
            foreach ($this->translateSql($part) ?? [] as $stmt) {
                $result = parent::exec($stmt);
            }
        }
        return $result;
    }

    public function query(...$args)
    {
        if (isset($args[0]) && is_string($args[0])) {
            $last = $this->execLeading($args[0]);
            if ($last === null) {
                return parent::query('SELECT 1 WHERE 0');
            }
            $args[0] = $last;
        }
        return parent::query(...$args);
    }

    public function prepare($sql)
    {
        $statements = $this->translateSql($sql);
        if ($statements !== null && count($statements) !== 1) {
            throw new \RuntimeException('SQLite compatibility cannot prepare multiple statements.');
        }
        return parent::prepare($statements[0] ?? 'SELECT 1 WHERE 0');
    }

    public function executeQuery($sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        $last = $this->execLeading($sql, $params !== []);
        if ($last === null) {
            return parent::executeQuery('SELECT 1 WHERE 0', [], []);
        }
        return parent::executeQuery($last, $params, $types, $qcp);
    }

    public function executeStatement($sql, array $params = [], array $types = [])
    {
        $last = $this->execLeading($sql, $params !== []);
        if ($last === null) {
            return 0;
        }
        return parent::executeStatement($last, $params, $types);
    }

    /**
     * Translate and execute all leading statements, returning the final one.
     *
     * @return string|null The last translated statement to execute, or null to skip.
     */
    private function execLeading(string $sql, bool $hasParams = false): ?string
    {
        $error = 'SQLite compatibility cannot execute multiple statements with parameters.';
        $parts = $this->splitStatements($sql);
        if ($hasParams && count($parts) > 1) {
            throw new \RuntimeException($error);
        }
        $statements = [];
        foreach ($parts as $part) {
            foreach ($statements as $stmt) {
                parent::exec($stmt);
            }
            $statements = $this->translateSql($part) ?? [];
        }
        if ($hasParams && count($statements) > 1) {
            throw new \RuntimeException($error);
        }
        $last = array_pop($statements);
        foreach ($statements as $stmt) {
            parent::exec($stmt);
        }
        return $last;
    }

    /**
     * Remove comments and split SQL into statements, keeping trigger bodies.
     *
     * @return string[]
     */
    private function splitStatements(string $sql): array
    {
        // Remove comments without touching quoted values or identifiers.
        $sql = preg_replace_callback('/' . self::SQL_PROTECTED . '/s', function (array $m): string {
            return str_starts_with($m[0], '--') || str_starts_with($m[0], '/*') ? ' ' : $m[0];
        }, $sql);
        // Trigger bodies contain semicolons that belong to one SQLite statement.
        if (preg_match('/^CREATE\s+(?:(?:TEMP|TEMPORARY)\s+)?TRIGGER\b/i', trim($sql))) {
            return [$sql];
        }
        return $this->splitSql($sql, ';');
    }

    /**
     * Translate MySQL-specific SQL to SQLite equivalents.
     *
     * Returns null to suppress execution entirely (e.g. SET NAMES).
     * Returns a single-element array for direct translations.
     * Returns a multi-element array for compound statements (e.g. CREATE TABLE
     * followed by CREATE INDEX statements).
     *
     * @return string[]|null
     */
    protected function translateSql(string $sql): ?array
    {
        $parts = $this->splitStatements($sql);
        if (count($parts) > 1) {
            $result = [];
            foreach ($parts as $part) {
                $translated = $this->translateSql($part);
                if ($translated !== null) {
                    array_push($result, ...$translated);
                }
            }
            return $result ?: null;
        }
        $sql = $parts[0] ?? '';

        $sql = $this->translateFunctions($sql);
        $sql = preg_replace('/^(\s*)INSERT\s+IGNORE\b/i', '$1INSERT OR IGNORE', $sql);
        $trimmed = trim($sql, " \t\n\r\0\x0B;");

        preg_match('/^\w+/', $trimmed, $keyword);
        if (!in_array(strtoupper($keyword[0] ?? ''), self::TRANSLATABLE_KEYWORDS, true)) {
            return [$sql];
        }

        if (preg_match('/^SHOW\s+(FULL\s+)?TABLES(?:\s+LIKE\s+(.+))?$/is', $trimmed, $m)) {
            $type = empty($m[1]) ? '' : ", 'BASE TABLE' AS Table_type";
            $filter = isset($m[2]) ? ' AND name LIKE ' . $m[2] : '';
            return ["SELECT name AS Tables_in_main$type FROM sqlite_master WHERE type='table' AND name NOT GLOB 'sqlite_*'$filter ORDER BY name"];
        }

        if (preg_match('/^SHOW\s+(?:INDEX(?:ES)?|KEYS)\s+(?:FROM|IN)\s+[`"\']?(\w+)[`"\']?(?:\s+WHERE\s+(.+))?$/is', $trimmed, $m)) {
            return [$this->translateShowIndex($m[1], $m[2] ?? null)];
        }

        if (preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=\s*([01])$/i', $trimmed, $m)) {
            if ($this->isTransactionActive()) {
                throw new \RuntimeException('SQLite cannot change foreign key enforcement inside a transaction.');
            }
            return ['PRAGMA foreign_keys = ' . ($m[1] === '1' ? 'ON' : 'OFF')];
        }

        // SQLite has no connection charset setting; do not suppress other SETs.
        if (preg_match('/^SET\s+NAMES\s+[\w\'"`]+(?:\s+COLLATE\s+[\w\'"`]+)?$/i', $trimmed)) {
            return null;
        }
        if (preg_match('/^SET\b/i', $trimmed)) {
            throw new \RuntimeException('Unsupported MySQL SET statement on SQLite: ' . $trimmed);
        }

        if (preg_match('/^(?:SHOW\s+COLUMNS\s+FROM|DESCRIBE|DESC)\s+[`"\']?(\w+)[`"\']?(?:\s+LIKE\s+(.+))?$/is', $trimmed, $m)) {
            $filter = isset($m[2]) ? ' WHERE name LIKE ' . $m[2] : '';
            return ["SELECT name AS Field, type AS Type, CASE WHEN \"notnull\" OR pk THEN 'NO' ELSE 'YES' END AS \"Null\", CASE WHEN pk THEN 'PRI' ELSE '' END AS \"Key\", dflt_value AS \"Default\", '' AS Extra FROM pragma_table_info('$m[1]')$filter ORDER BY cid"];
        }

        if (preg_match('/^TRUNCATE\s+(?:TABLE\s+)?[`"\']?(\w+)[`"\']?$/i', $trimmed, $m)) {
            $statements = ['DELETE FROM `' . $m[1] . '`'];
            if ($this->fetchOne("SELECT 1 FROM sqlite_master WHERE name='sqlite_sequence'")) {
                $statements[] = "DELETE FROM sqlite_sequence WHERE name='$m[1]'";
            }
            return $statements;
        }

        if (preg_match('/^CREATE\s+TABLE\s+/i', $trimmed)) {
            return $this->translateCreateTable($trimmed);
        }

        if (preg_match('/^ALTER\s+TABLE\s+.+\s+ADD\s+(?:CONSTRAINT\s+.+\s+)?FOREIGN\s+KEY/is', $trimmed)) {
            return $this->translateAddForeignKeys($trimmed);
        }

        return [$sql];
    }

    /**
     * Translate ALTER TABLE … ADD [CONSTRAINT name] FOREIGN KEY clauses.
     *
     * SQLite cannot add a foreign key to an existing table, so the table is
     * rebuilt by DBAL (copy to a temporary table, recreate with the new keys,
     * copy back, recreate indexes). Enforcement is switched off during the
     * rebuild so dropping the old table never cascades into child tables,
     * then restored. PRAGMA foreign_keys is a no-op inside a transaction, so
     * a rebuild with enforcement on inside a transaction is refused.
     *
     * @return string[]
     */
    private function translateAddForeignKeys(string $sql): array
    {
        $id = '(?:`([^`]+)`|"([^"]+)"|(\w+))';
        $unquote = function (string $name): string {
            return trim($name, " \t\n\r`\"");
        };
        $columns = function (string $list) use ($unquote): array {
            return array_map($unquote, explode(',', $list));
        };
        $action = '(CASCADE|SET\s+NULL|SET\s+DEFAULT|RESTRICT|NO\s+ACTION)';
        if (!preg_match('/^ALTER\s+TABLE\s+' . $id . '\s+(.+)$/is', $sql, $m)) {
            throw new \RuntimeException('Unsupported ALTER TABLE foreign key statement on SQLite: ' . $sql);
        }
        $tableName = $m[1] . $m[2] . $m[3];
        $foreignKeys = [];
        foreach ($this->splitSql($m[4], ',') as $clause) {
            if (!preg_match('/^ADD\s+(?:CONSTRAINT\s+' . $id . '\s+)?FOREIGN\s+KEY\s*(?:' . $id . '\s*)?\(([^)]+)\)\s*REFERENCES\s+' . $id . '\s*\(([^)]+)\)((?:\s+ON\s+(?:DELETE|UPDATE)\s+' . $action . ')*)$/is', trim($clause), $fk)) {
                throw new \RuntimeException('SQLite can only rebuild a table for ALTER TABLE statements that add foreign keys: ' . $sql);
            }
            $options = [];
            preg_match_all('/ON\s+(DELETE|UPDATE)\s+' . $action . '/i', $fk[12], $actions, PREG_SET_ORDER);
            foreach ($actions as $a) {
                $options['on' . ucfirst(strtolower($a[1]))] = strtoupper(preg_replace('/\s+/', ' ', $a[2]));
            }
            $foreignKeys[] = new ForeignKeyConstraint(
                $columns($fk[7]),
                $fk[8] . $fk[9] . $fk[10],
                $columns($fk[11]),
                ($fk[1] . $fk[2] . $fk[3]) ?: null,
                $options
            );
        }

        $enforced = (bool) $this->fetchOne('PRAGMA foreign_keys');
        if ($enforced && $this->isTransactionActive()) {
            throw new \RuntimeException('SQLite cannot rebuild a table to add a foreign key inside a transaction while foreign keys are enforced.');
        }
        // DBAL 2 reports no foreign key support for SQLite, so listTableDetails()
        // omits the existing keys; add them or the rebuild would drop them.
        $schemaManager = $this->getSchemaManager();
        $table = $schemaManager->listTableDetails($tableName);
        $fromTable = new Table($tableName, $table->getColumns(), $table->getIndexes(), $schemaManager->listTableForeignKeys($tableName), 0, $table->getOptions());
        // Table adds implicit indexes for foreign keys; they do not exist here.
        foreach (array_diff_key($fromTable->getIndexes(), $table->getIndexes()) as $name => $index) {
            $fromTable->dropIndex($name);
        }
        $diff = new TableDiff($tableName, [], [], [], [], [], [], $fromTable);
        $diff->addedForeignKeys = $foreignKeys;
        $statements = $this->getDatabasePlatform()->getAlterTableSQL($diff);
        return $enforced
            ? array_merge(['PRAGMA foreign_keys = OFF'], $statements, ['PRAGMA foreign_keys = ON'])
            : $statements;
    }

    private function translateShowIndex(string $table, ?string $where): string
    {
        if (!$this->fetchOne("SELECT 1 FROM sqlite_master WHERE type='table' AND name=? COLLATE NOCASE UNION ALL SELECT 1 FROM sqlite_temp_master WHERE type='table' AND name=? COLLATE NOCASE", [$table, $table])) {
            throw new \RuntimeException('Cannot show indexes: table does not exist: ' . $table);
        }
        // Integer rowid primary keys have no entry in pragma_index_list.
        // shortcut: expose physical SQLite index names, port name-based migrations explicitly.
        $sql = <<<SQL
WITH index_columns AS (
    SELECT CASE WHEN il.origin = 'pk' THEN 'PRIMARY' ELSE il.name END AS index_name,
        1 - il."unique" AS non_unique, ix.seqno + 1 AS position,
        ix.name AS column_name, ix."desc" AS descending
    FROM pragma_index_list('$table') AS il
    JOIN pragma_index_xinfo(il.name) AS ix
    WHERE ix."key" = 1
    UNION ALL
    SELECT 'PRIMARY', 0, pk, name, 0 FROM pragma_table_info('$table')
    WHERE pk > 0 AND NOT EXISTS (SELECT 1 FROM pragma_index_list('$table') WHERE origin = 'pk')
)
SELECT * FROM (
    SELECT '$table' AS "Table", ic.non_unique AS Non_unique,
        ic.index_name AS Key_name, ic.position AS Seq_in_index,
        ic.column_name AS Column_name,
        CASE WHEN ic.descending THEN 'D' ELSE 'A' END AS Collation,
        NULL AS Cardinality, NULL AS Sub_part, NULL AS Packed,
        CASE WHEN ti."notnull" OR ti.pk THEN '' ELSE 'YES' END AS "Null",
        'BTREE' AS Index_type, '' AS Comment, '' AS Index_comment
    FROM index_columns AS ic
    LEFT JOIN pragma_table_xinfo('$table') AS ti ON ti.name = ic.column_name
)
SQL;
        if ($where !== null) {
            $sql .= ' WHERE ' . $where;
        }
        return $sql . ' ORDER BY Key_name, Seq_in_index';
    }

    /**
     * Replace MySQL current-date/time functions with SQLite equivalents,
     * leaving any text inside quoted strings/identifiers untouched so a
     * literal value such as 'see NOW() docs' is never corrupted.
     */
    private function translateFunctions(string $sql): string
    {
        // Cheap bail-out: skip the scan when there is nothing to translate.
        if (!preg_match('/\b(?:NOW|CURDATE|CURTIME|UTC_TIMESTAMP|UTC_DATE|UTC_TIME)\s*\(\s*\)/i', $sql)) {
            return $sql;
        }

        $parts = preg_split('/(' . self::SQL_PROTECTED . ')/s', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => &$part) {
            if ($i % 2 === 0) {
                $part = $this->replaceFunctions($part);
            }
        }
        return implode('', $parts);
    }

    /**
     * Apply the MySQL→SQLite function substitutions to unquoted SQL text.
     */
    private function replaceFunctions(string $sql): string
    {
        return preg_replace(
            [
                '/\bNOW\s*\(\s*\)/i',
                '/\bUTC_TIMESTAMP\s*\(\s*\)/i',
                '/\bCURDATE\s*\(\s*\)/i',
                '/\bUTC_DATE\s*\(\s*\)/i',
                '/\bCURTIME\s*\(\s*\)/i',
                '/\bUTC_TIME\s*\(\s*\)/i',
            ],
            [
                'CURRENT_TIMESTAMP',
                'CURRENT_TIMESTAMP',
                'CURRENT_DATE',
                'CURRENT_DATE',
                'CURRENT_TIME',
                'CURRENT_TIME',
            ],
            $sql
        );
    }

    /**
     * Translate a MySQL CREATE TABLE statement to SQLite-compatible DDL.
     *
     * Extracts inline INDEX/KEY definitions into separate CREATE INDEX
     * statements and strips MySQL-specific column options (AUTO_INCREMENT,
     * COMMENT, COLLATE, ENGINE, CHARSET, etc.).
     *
     * @return string[]
     */
    private function translateCreateTable(string $sql): array
    {
        // Extract table name.
        if (!preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\']?(\w+)[`"\']?\s*\(/i', $sql, $m)) {
            return [$sql];
        }
        $tableName = $m[1];

        // Find the body between the outermost parentheses.
        $openParen = strpos($sql, '(');
        preg_match_all('/' . self::SQL_PROTECTED . '|[()]/s', $sql, $tokens, PREG_OFFSET_CAPTURE);
        $depth = 0;
        $closeParen = null;
        foreach ($tokens[0] as [$token, $offset]) {
            if ($token === '(') {
                $depth++;
            } elseif ($token === ')' && --$depth === 0) {
                $closeParen = $offset;
                break;
            }
        }
        if ($openParen === false || $closeParen === null) {
            return [$sql];
        }
        $body = substr($sql, $openParen + 1, $closeParen - $openParen - 1);

        // Split body into lines by comma, respecting parenthesized expressions.
        $lines = $this->splitSql($body, ',');

        $columns = [];
        $createIndexes = [];
        $autoIncrementColumn = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\s*FULLTEXT\s+(KEY|INDEX)\s+/i', $line)) {
                throw new \RuntimeException('MySQL FULLTEXT indexes require a SQLite-specific search implementation.');
            }

            // Extract KEY/INDEX into separate CREATE INDEX statements. The index
            // name is optional in MySQL, e.g. "UNIQUE INDEX (`a`, `b`)" or
            // "KEY (`a`)"; SQLite forbids inline (non-primary) indexes, so both
            // named and unnamed forms must become standalone CREATE INDEX.
            if (preg_match('/^\s*(?:(UNIQUE)\s+)?(?:KEY|INDEX)\s*(?:[`"\']?(\w+)[`"\']?\s*)?(\(.*\))/i', $line, $km)) {
                $isUnique = ($km[1] ?? '') !== '' ? 'UNIQUE ' : '';
                // Preserve prefix uniqueness with expression indexes on SQLite.
                $indexCols = preg_replace_callback('/([`"]?\w+[`"]?)\s*\((\d+)\)/', function (array $m) use ($isUnique): string {
                    return $isUnique ? 'substr(' . $m[1] . ', 1, ' . $m[2] . ')' : $m[1];
                }, $km[3]);
                $indexName = ($km[2] ?? '') !== ''
                    ? $tableName . '_' . $km[2]
                    : $this->generateIndexName($tableName, $indexCols, $isUnique !== '');
                $ifNotExists = preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $sql) ? 'IF NOT EXISTS ' : '';
                $createIndexes[] = "CREATE {$isUnique}INDEX {$ifNotExists}`{$indexName}` ON `{$tableName}` {$indexCols}";
                continue;
            }

            // Keep CONSTRAINT, PRIMARY KEY, and column definitions.
            // Apply column-level translations.
            if (preg_match('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\bAUTO_INCREMENT\b/is', $line)) {
                if ($autoIncrementColumn !== null || !preg_match('/^[`"]?(\w+)[`"]?\s+(?:TINY|SMALL|MEDIUM|BIG)?INT\b/i', $line, $column)) {
                    throw new \RuntimeException('SQLite AUTO_INCREMENT requires one integer primary key.');
                }
                $autoIncrementColumn = $column[1];
            }
            $line = $this->translateColumnDef($line);
            $columns[] = $line;
        }

        if ($autoIncrementColumn !== null) {
            $hasPrimaryKey = false;
            foreach ($columns as $i => $column) {
                if (preg_match('/^PRIMARY\s+KEY\s*\(\s*[`"]?' . $autoIncrementColumn . '[`"]?\s*\)$/i', $column)) {
                    unset($columns[$i]);
                    $hasPrimaryKey = true;
                } elseif (preg_match('/^[`"]?' . $autoIncrementColumn . '[`"]?\s/i', $column) && preg_match('/\bPRIMARY\s+KEY\b/i', $column)) {
                    $hasPrimaryKey = true;
                } elseif (preg_match('/^PRIMARY\s+KEY\b/i', $column)) {
                    throw new \RuntimeException('SQLite AUTO_INCREMENT cannot use a composite primary key.');
                }
            }
            if (!$hasPrimaryKey) {
                throw new \RuntimeException('SQLite AUTO_INCREMENT requires an integer primary key.');
            }
            foreach ($columns as &$column) {
                if (preg_match('/^[`"]?' . $autoIncrementColumn . '[`"]?\s/i', $column)) {
                    $column .= (preg_match('/\bPRIMARY\s+KEY\b/i', $column) ? '' : ' PRIMARY KEY') . ' AUTOINCREMENT';
                }
            }
            unset($column);
        }

        $ifNotExists = preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $sql) ? 'IF NOT EXISTS ' : '';
        $columnsStr = implode(",\n  ", $columns);
        $suffix = substr($sql, $closeParen + 1);
        $suffix = preg_replace('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\s*(?:ENGINE\s*=?\s*\w+|(?:DEFAULT\s+)?(?:CHARSET|CHARACTER\s+SET)\s*=?\s*\w+|COLLATE\s*=?\s*(?:\w+|`\w+`|"\w+"|\'\w+\')|COMMENT\s*=?\s*\'(?:[^\']|\'\')*\')/is', '', $suffix);
        $result = ["CREATE TABLE {$ifNotExists}`{$tableName}` (\n  {$columnsStr}\n)$suffix"];

        // Append CREATE INDEX statements.
        foreach ($createIndexes as $idx) {
            $result[] = $idx;
        }

        return $result;
    }

    /**
     * Translate a single column definition or constraint from MySQL to SQLite.
     */
    private function translateColumnDef(string $line): string
    {
        $skip = '(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|';
        $line = preg_replace('/' . $skip . '\b(?:TINY|SMALL|MEDIUM|BIG)?INT\b(?:\s*\(\d+\))?/is', 'INTEGER', $line);
        $line = preg_replace('/' . $skip . '\b(?:LONG|MEDIUM|TINY)TEXT\b/is', 'TEXT', $line);
        $line = preg_replace('/' . $skip . '\b(?:VAR)?BINARY\b(?:\s*\(\d+\))?/is', 'BLOB', $line);
        $line = preg_replace('/' . $skip . '\b(?:LONG|MEDIUM|TINY)BLOB\b/is', 'BLOB', $line);
        $line = preg_replace('/' . $skip . '\s+(?:AUTO_INCREMENT|UNSIGNED)\b/is', '', $line);
        $line = preg_replace('/' . $skip . '\s+COLLATE\s+[`"\']?\w+[`"\']?/is', '', $line);
        $line = preg_replace('/' . $skip . '\s+COMMENT\s+(?:\'(?:[^\']|\'\')*\'|"(?:[^"]|"")*")/is', '', $line);
        $line = preg_replace('/' . $skip . '\s+CHARACTER\s+SET\s+\w+/is', '', $line);
        $line = preg_replace('/^(\s*)UNIQUE\s+KEY\s+[`"\']?\w+[`"\']?\s*/i', '$1UNIQUE ', $line);

        return $line;
    }

    /**
     * Build a deterministic index name for an unnamed inline MySQL index.
     *
     * SQLite requires every index to have a (schema-unique) name, while MySQL
     * allows anonymous inline indexes. The name is derived from the table and
     * the referenced columns so it stays stable and collision-free.
     */
    private function generateIndexName(string $tableName, string $cols, bool $isUnique): string
    {
        $clean = trim((string) preg_replace('/[^a-zA-Z0-9_]+/', '_', $cols), '_');
        $prefix = $isUnique ? 'uniq' : 'idx';
        return "{$prefix}_{$tableName}_{$clean}";
    }

    /**
     * Split SQL outside quoted regions, comments and parenthesized expressions.
     *
     * @return string[]
     */
    private function splitSql(string $sql, string $delimiter): array
    {
        preg_match_all('/' . self::SQL_PROTECTED . '|[();,]/s', $sql, $tokens, PREG_OFFSET_CAPTURE);
        $parts = [];
        $start = 0;
        $depth = 0;
        foreach ($tokens[0] as [$token, $offset]) {
            if ($token === '(') {
                $depth++;
            } elseif ($token === ')') {
                $depth--;
            } elseif ($token === $delimiter && $depth === 0) {
                $parts[] = trim(substr($sql, $start, $offset - $start));
                $start = $offset + 1;
            }
        }
        $parts[] = trim(substr($sql, $start));
        return array_values(array_filter($parts, function (string $part): bool {
            return $part !== '';
        }));
    }

    /**
     * Register emulations of common MySQL SQL functions that SQLite lacks.
     *
     * Third-party modules that build raw SQL almost always write it in MySQL
     * dialect, so functions such as FROM_UNIXTIME() fail on SQLite with
     * "no such function". SQLite supports user defined functions, so the most
     * common MySQL date/time and string helpers are emulated here with MySQL
     * semantics (e.g. CONCAT() returns NULL when any argument is NULL, unlike
     * the SQLite 3.44+ built-in it overrides). translateFunctions() already
     * handles the no-arg date functions (NOW(), CURDATE(), ...) via plain text
     * substitution; the functions below need real arguments, so a UDF is used
     * instead of a regex rewrite.
     */
    private function registerMysqlFunctions(): void
    {
        $pdo = $this->_conn;
        if (!$pdo instanceof \PDO) {
            return;
        }

        $create = function (string $name, callable $callback, int $numArgs) use ($pdo): void {
            if (method_exists($pdo, 'createFunction')) {
                // Pdo\Sqlite subclass: Omeka\Db\Driver\SqlitePdoConnection on PHP 8.4+.
                $pdo->createFunction($name, $callback, $numArgs);
            } elseif (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction($name, $callback, $numArgs);
            }
        };

        $create('from_unixtime', function ($timestamp, $format = null) {
            if ($timestamp === null || !is_numeric($timestamp)) {
                return null;
            }
            $timestamp = (int) $timestamp;
            if ($format === null) {
                return date('Y-m-d H:i:s', $timestamp);
            }
            return $this->formatMysqlDate($timestamp, (string) $format);
        }, -1);
        $create('unix_timestamp', function ($datetime = null) {
            if (func_num_args() === 0) {
                return time();
            }
            if ($datetime === null) {
                return null;
            }
            $timestamp = strtotime((string) $datetime);
            return $timestamp === false ? null : $timestamp;
        }, -1);
        $create('date_format', function ($datetime, $format) {
            if ($datetime === null || $format === null) {
                return null;
            }
            $timestamp = strtotime((string) $datetime);
            if ($timestamp === false) {
                return null;
            }
            return $this->formatMysqlDate($timestamp, (string) $format);
        }, 2);
        $create('if', function ($condition, $ifTrue, $ifFalse) {
            return $condition ? $ifTrue : $ifFalse;
        }, 3);
        $create('md5', function ($value) {
            return $value === null ? null : md5((string) $value);
        }, 1);
        $create('concat', function (...$args) {
            foreach ($args as $arg) {
                if ($arg === null) {
                    return null;
                }
            }
            return implode('', array_map('strval', $args));
        }, -1);
        $create('concat_ws', function ($separator, ...$args) {
            if ($separator === null) {
                return null;
            }
            $parts = [];
            foreach ($args as $arg) {
                if ($arg !== null) {
                    $parts[] = (string) $arg;
                }
            }
            return implode((string) $separator, $parts);
        }, -1);
        $create('field', function ($needle, ...$haystack) {
            // MySQL: 1-based position of needle in the list, 0 if absent or
            // needle is NULL; string comparison is case-insensitive.
            if ($needle === null) {
                return 0;
            }
            foreach ($haystack as $i => $candidate) {
                if ($candidate === null) {
                    continue;
                }
                if (is_numeric($needle) && is_numeric($candidate)) {
                    if ((float) $needle === (float) $candidate) {
                        return $i + 1;
                    }
                } elseif (strcasecmp((string) $needle, (string) $candidate) === 0) {
                    return $i + 1;
                }
            }
            return 0;
        }, -1);
        $create('find_in_set', function ($needle, $strlist) {
            // MySQL: NULL if either argument is NULL, 0 if the list is empty
            // or the needle is absent, else the 1-based position in the
            // comma-separated list; comparison is case-insensitive.
            if ($needle === null || $strlist === null) {
                return null;
            }
            $strlist = (string) $strlist;
            if ($strlist === '') {
                return 0;
            }
            foreach (explode(',', $strlist) as $i => $candidate) {
                if (strcasecmp((string) $needle, $candidate) === 0) {
                    return $i + 1;
                }
            }
            return 0;
        }, 2);
        $create('greatest', function (...$args) {
            // MySQL: NULL if any argument is NULL; numeric comparison when all
            // arguments are numeric, string comparison otherwise.
            if (in_array(null, $args, true)) {
                return null;
            }
            $allNumeric = !in_array(false, array_map('is_numeric', $args), true);
            return $allNumeric
                ? max(array_map(function ($v) { return $v + 0; }, $args))
                : max(array_map('strval', $args));
        }, -1);
        $create('least', function (...$args) {
            // MySQL: NULL if any argument is NULL; numeric comparison when all
            // arguments are numeric, string comparison otherwise.
            if (in_array(null, $args, true)) {
                return null;
            }
            $allNumeric = !in_array(false, array_map('is_numeric', $args), true);
            return $allNumeric
                ? min(array_map(function ($v) { return $v + 0; }, $args))
                : min(array_map('strval', $args));
        }, -1);
        $create('regexp', function ($pattern, $value) {
            // Backs SQLite's REGEXP operator ("x REGEXP y" calls regexp(y, x)),
            // which has no default implementation. MySQL returns NULL when
            // either operand is NULL.
            if ($pattern === null || $value === null) {
                return null;
            }
            $regex = '/' . str_replace('/', '\\/', (string) $pattern) . '/u';
            return @preg_match($regex, (string) $value) === 1 ? 1 : 0;
        }, 2);
    }

    /**
     * Format a unix timestamp using a MySQL DATE_FORMAT() format string.
     *
     * Covers the commonly used specifiers; week-based specifiers (%u, %v, %V,
     * %x, %X) are approximated with their ISO-8601 equivalents. As in MySQL,
     * unknown specifiers yield the literal character.
     */
    private function formatMysqlDate(int $timestamp, string $format): string
    {
        return preg_replace_callback('/%(.)/', function (array $matches) use ($timestamp): string {
            switch ($matches[1]) {
                case 'Y': return date('Y', $timestamp);
                case 'y': return date('y', $timestamp);
                case 'M': return date('F', $timestamp);
                case 'b': return date('M', $timestamp);
                case 'm': return date('m', $timestamp);
                case 'c': return date('n', $timestamp);
                case 'D': return date('jS', $timestamp);
                case 'd': return date('d', $timestamp);
                case 'e': return date('j', $timestamp);
                case 'j': return sprintf('%03d', (int) date('z', $timestamp) + 1);
                case 'H': return date('H', $timestamp);
                case 'k': return date('G', $timestamp);
                case 'h': // 12-hour, zero-padded, same as %I.
                case 'I': return date('h', $timestamp);
                case 'l': return date('g', $timestamp);
                case 'i': return date('i', $timestamp);
                case 'S': // Seconds, same as %s.
                case 's': return date('s', $timestamp);
                case 'f': return sprintf('%06d', (int) date('u', $timestamp));
                case 'p': return date('A', $timestamp);
                case 'r': return date('h:i:s A', $timestamp);
                case 'T': return date('H:i:s', $timestamp);
                case 'W': return date('l', $timestamp);
                case 'a': return date('D', $timestamp);
                case 'w': return date('w', $timestamp);
                case 'u': // Week-based specifiers approximated as ISO-8601 week.
                case 'v':
                case 'V': return date('W', $timestamp);
                case 'x': // ISO-8601 week-numbering year for %x and %X.
                case 'X': return date('o', $timestamp);
                case '%': return '%';
                default:  return $matches[1];
            }
        }, $format);
    }
}
