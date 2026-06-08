<?php
namespace Omeka\Db\Connection;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;

/**
 * A DBAL Connection wrapper that transparently translates MySQL-specific SQL
 * to SQLite equivalents. This allows third-party modules that use MySQL-only
 * syntax (SHOW TABLES, SET FOREIGN_KEY_CHECKS, etc.) to work with SQLite.
 */
class SqliteCompatConnection extends Connection
{
    private const TRANSLATABLE_KEYWORDS = ['SHOW', 'SET', 'TRUNCATE', 'DESCRIBE', 'DESC', 'CREATE', 'ALTER'];

    public function exec($sql): int
    {
        $statements = $this->translateSql($sql);
        if ($statements === null) {
            return 0;
        }
        $result = 0;
        foreach ($statements as $stmt) {
            $result = parent::exec($stmt);
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

    public function executeQuery($sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        $last = $this->execLeading($sql);
        if ($last === null) {
            return parent::executeQuery('SELECT 1 WHERE 0', [], []);
        }
        return parent::executeQuery($last, $params, $types, $qcp);
    }

    public function executeStatement($sql, array $params = [], array $types = [])
    {
        $last = $this->execLeading($sql);
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
    private function execLeading(string $sql): ?string
    {
        $statements = $this->translateSql($sql);
        if ($statements === null) {
            return null;
        }
        for ($i = 0, $last = count($statements) - 1; $i < $last; $i++) {
            parent::exec($statements[$i]);
        }
        return $statements[$last];
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
        $trimmed = trim($sql, " \t\n\r\0\x0B;");

        // Fast path: most queries don't need translation.
        $firstSpace = strpos($trimmed, ' ');
        $firstWord = $firstSpace !== false ? strtoupper(substr($trimmed, 0, $firstSpace)) : strtoupper($trimmed);
        if (!in_array($firstWord, self::TRANSLATABLE_KEYWORDS, true)) {
            return [$sql];
        }

        if (preg_match('/^SHOW\s+(FULL\s+)?TABLES/i', $trimmed)) {
            return ["SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"];
        }

        if (preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=\s*0/i', $trimmed)) {
            return ['PRAGMA foreign_keys = OFF'];
        }
        if (preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=\s*1/i', $trimmed)) {
            return ['PRAGMA foreign_keys = ON'];
        }

        // SET NAMES and other unsupported SET statements are no-ops for SQLite.
        if (preg_match('/^SET\s+/i', $trimmed)) {
            return null;
        }

        if (preg_match('/^(?:SHOW\s+COLUMNS\s+FROM|DESCRIBE|DESC)\s+[`"\']?(\w+)[`"\']?/i', $trimmed, $m)) {
            return ['PRAGMA table_info(' . $m[1] . ')'];
        }

        if (preg_match('/^TRUNCATE\s+(?:TABLE\s+)?[`"\']?(\w+)[`"\']?/i', $trimmed, $m)) {
            return ['DELETE FROM ' . $m[1]];
        }

        // Translate MySQL CREATE TABLE to SQLite-compatible DDL.
        if (preg_match('/^CREATE\s+TABLE\s+/i', $trimmed)) {
            return $this->translateCreateTable($trimmed);
        }

        // SQLite doesn't support ALTER TABLE ADD CONSTRAINT FOREIGN KEY.
        // Skip these since FKs are already defined inline in CREATE TABLE.
        if (preg_match('/^ALTER\s+TABLE\s+.+\s+ADD\s+CONSTRAINT\s+.+\s+FOREIGN\s+KEY/i', $trimmed)) {
            return null;
        }

        // Compound statements like "SET FOREIGN_KEY_CHECKS=0; DROP TABLE x".
        if (str_contains($trimmed, ';')) {
            $parts = array_filter(array_map('trim', explode(';', $trimmed)));
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
        }

        return [$sql];
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
        $closeParen = strrpos($sql, ')');
        if ($openParen === false || $closeParen === false) {
            return [$sql];
        }
        $body = substr($sql, $openParen + 1, $closeParen - $openParen - 1);

        // Split body into lines by comma, respecting parenthesized expressions.
        $lines = $this->splitByComma($body);

        $columns = [];
        $createIndexes = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Skip FULLTEXT KEY/INDEX (handled in application code).
            if (preg_match('/^\s*FULLTEXT\s+(KEY|INDEX)\s+/i', $line)) {
                continue;
            }

            // Extract KEY/INDEX into separate CREATE INDEX statements. The index
            // name is optional in MySQL, e.g. "UNIQUE INDEX (`a`, `b`)" or
            // "KEY (`a`)"; SQLite forbids inline (non-primary) indexes, so both
            // named and unnamed forms must become standalone CREATE INDEX.
            if (preg_match('/^\s*(?:(UNIQUE)\s+)?(?:KEY|INDEX)\s*(?:[`"\']?(\w+)[`"\']?\s*)?(\(.*\))/i', $line, $km)) {
                $isUnique = ($km[1] ?? '') !== '' ? 'UNIQUE ' : '';
                // Strip prefix lengths like col(191) → col (SQLite doesn't support them).
                $indexCols = preg_replace('/(\w)`?\s*\(\d+\)/', '$1`', $km[3]);
                $indexName = ($km[2] ?? '') !== ''
                    ? $km[2]
                    : $this->generateIndexName($tableName, $indexCols, $isUnique !== '');
                $createIndexes[] = "CREATE {$isUnique}INDEX `{$indexName}` ON `{$tableName}` {$indexCols}";
                continue;
            }

            // Keep CONSTRAINT, PRIMARY KEY, and column definitions.
            // Apply column-level translations.
            $line = $this->translateColumnDef($line);
            $columns[] = $line;
        }

        $ifNotExists = preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $sql) ? 'IF NOT EXISTS ' : '';
        $columnsStr = implode(",\n  ", $columns);
        $result = ["CREATE TABLE {$ifNotExists}`{$tableName}` (\n  {$columnsStr}\n)"];

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
        $line = preg_replace('/\b(?:TINY|SMALL|MEDIUM|BIG)?INT\b(?:\s*\(\d+\))?/i', 'INTEGER', $line);
        $line = preg_replace('/\b(?:LONG|MEDIUM|TINY)TEXT\b/i', 'TEXT', $line);
        $line = preg_replace('/\b(?:VAR)?BINARY\b(?:\s*\(\d+\))?/i', 'BLOB', $line);
        $line = preg_replace('/\b(?:LONG|MEDIUM|TINY)BLOB\b/i', 'BLOB', $line);
        $line = preg_replace('/\s+AUTO_INCREMENT/i', '', $line);
        $line = preg_replace('/\s+COLLATE\s+[`"\']?\w+[`"\']?/i', '', $line);
        $line = preg_replace('/\s+COMMENT\s+(?:\'[^\']*\'|"[^"]*")/i', '', $line);
        $line = preg_replace('/\s+CHARACTER\s+SET\s+\w+/i', '', $line);
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
     * Split a string by commas, respecting parenthesized expressions.
     *
     * @return string[]
     */
    private function splitByComma(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        for ($i = 0, $len = strlen($body); $i < $len; $i++) {
            $char = $body[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }
            $current .= $char;
        }
        if (trim($current) !== '') {
            $parts[] = $current;
        }
        return $parts;
    }
}
