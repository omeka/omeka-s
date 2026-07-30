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
        // Rewrite MySQL scalar functions (NOW(), etc.) on every statement,
        // including INSERT/UPDATE which skip the keyword fast path below.
        $sql = $this->translateFunctions($sql);

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

        $result = '';
        $buffer = '';
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            if ($char === "'" || $char === '"' || $char === '`') {
                // Flush the unquoted buffer (translated) before copying the
                // quoted region verbatim.
                $result .= $this->replaceFunctions($buffer);
                $buffer = '';
                $quote = $char;
                $result .= $char;
                for ($i++; $i < $len; $i++) {
                    $c = $sql[$i];
                    // Backslash escape inside '...'/"..." (not for `identifiers`).
                    if ($c === '\\' && $quote !== '`' && $i + 1 < $len) {
                        $result .= $c . $sql[$i + 1];
                        $i++;
                        continue;
                    }
                    if ($c === $quote) {
                        // Doubled quote ('' / "" / ``) is an escaped quote.
                        if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                            $result .= $c . $quote;
                            $i++;
                            continue;
                        }
                        $result .= $c;
                        break;
                    }
                    $result .= $c;
                }
                continue;
            }
            $buffer .= $char;
        }
        $result .= $this->replaceFunctions($buffer);

        return $result;
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
                // Pdo\Sqlite subclass (PHP 8.4+ PDO::connect()).
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
            if ($datetime === null) {
                return time();
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
