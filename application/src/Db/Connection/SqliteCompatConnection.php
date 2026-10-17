<?php
namespace Omeka\Db\Connection;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;

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
        $sql = $this->translateDateArithmetic($sql);
        $sql = $this->translateGroupConcat($sql);
        $sql = $this->translateMatch($sql);
        $sql = $this->translateTrimFrom($sql);
        $sql = $this->translateInformationSchema($sql);
        $sql = $this->translateJsonTable($sql);
        $sql = preg_replace('/^(\s*CREATE\s+)FULLTEXT\s+(INDEX\b)/i', '$1$2', $sql);
        $sql = preg_replace('/^(\s*)INSERT\s+IGNORE\b/i', '$1INSERT OR IGNORE', $sql);
        $sql = $this->translateUpsert($sql);
        $sql = $this->translateDeleteJoin($sql);
        $sql = $this->translateUpdateJoin($sql);
        // SQLite transactions are serializable: a write lock covers the whole
        // database, so row locks of locking reads are not needed.
        $sql = preg_replace('/^(\s*SELECT\b.*?)\s+(?:FOR\s+(?:UPDATE|SHARE)(?:\s+NOWAIT|\s+SKIP\s+LOCKED)?|LOCK\s+IN\s+SHARE\s+MODE)[\s;]*$/is', '$1', $sql);
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

        // No server variables: modules then take their portable code path.
        if (preg_match('/^SHOW\s+(?:(?:GLOBAL|SESSION)\s+)?VARIABLES\b/i', $trimmed)) {
            return ['SELECT NULL AS Variable_name, NULL AS Value WHERE 0'];
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
        // Nor SQL modes or a GROUP_CONCAT() length limit, and its clock is UTC.
        if (preg_match('/^SET\s+(?:(?:SESSION|LOCAL)\s+|@@(?:SESSION\.)?)?(?:SQL_MODE\s*=\s*(?:\'[^\']*\'|"[^"]*"|\w+)|GROUP_CONCAT_MAX_LEN\s*=\s*\d+|TIME_ZONE\s*=\s*([\'"])(?:\+00:00|UTC)\1)$/i', $trimmed)) {
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

        if (preg_match('/^ALTER\s+TABLE\s+/i', $trimmed)) {
            return $this->translateAlterIndexes($trimmed) ?? $this->translateAlterTable($trimmed) ?? [$sql];
        }

        return [$sql];
    }

    /**
     * Translate DATE_ADD(), DATE_SUB(), ADDDATE() and SUBDATE() into the
     * mysql_date_add() function, keeping arguments in order for parameters.
     */
    private function translateDateArithmetic(string $sql): string
    {
        return $this->replaceCalls($sql, 'DATE_ADD|DATE_SUB|ADDDATE|SUBDATE', function (string $function, string $arguments): ?string {
            $args = $this->splitSql($arguments, ',');
            if (count($args) !== 2) {
                return null;
            }
            if (preg_match('/^INTERVAL\s+(.+)\s+(SECOND|MINUTE|HOUR|DAY|WEEK|MONTH|QUARTER|YEAR)$/is', $args[1], $interval)) {
                [, $amount, $unit] = $interval;
            } elseif (in_array($function, ['ADDDATE', 'SUBDATE'], true)) {
                [$amount, $unit] = [$args[1], 'DAY'];
            } else {
                return null;
            }
            $sign = in_array($function, ['DATE_SUB', 'SUBDATE'], true) ? '-' : '';
            return "mysql_date_add($args[0], $sign($amount), '" . strtoupper($unit) . "')";
        });
    }

    /**
     * Translate GROUP_CONCAT([DISTINCT] expr[, expr…] [ORDER BY …] [SEPARATOR s]).
     *
     * MySQL concatenates several expressions, skipping rows where one is NULL,
     * while SQLite takes the separator as second argument. SQLite refuses
     * DISTINCT with a separator, so mysql_group_concat_distinct() does it.
     */
    private function translateGroupConcat(string $sql): string
    {
        return $this->replaceCalls($sql, 'GROUP_CONCAT', function (string $function, string $arguments) use ($sql): ?string {
            $distinct = preg_match('/^\s*DISTINCT\s+/i', $arguments, $d) ? $d[0] : '';
            $arguments = substr($arguments, strlen($distinct));
            $at = $this->topLevelKeywords($arguments, 'ORDER\s+BY|SEPARATOR');
            $expressions = $this->splitSql(substr($arguments, 0, $at ? min($at) : strlen($arguments)), ',');
            $separator = isset($at['SEPARATOR']) ? trim(substr($arguments, $at['SEPARATOR'] + 9)) : null;
            $orderBy = isset($at['ORDER']) ? ' ' . trim(substr($arguments, $at['ORDER'], ($at['SEPARATOR'] ?? strlen($arguments)) - $at['ORDER'])) : '';
            if (count($expressions) === 1 && $separator === null) {
                return null;
            }
            if (strpos($this->unquoted((string) $separator), '?') !== false && strpos($this->unquoted($orderBy), '?') !== false) {
                throw new \RuntimeException('SQLite compatibility cannot reorder positional parameters of a GROUP_CONCAT(): ' . $sql);
            }
            $value = count($expressions) === 1 ? $expressions[0] : '(' . implode(') || (', $expressions) . ')';
            if ($separator === null) {
                return "GROUP_CONCAT($distinct$value$orderBy)";
            }
            return ($distinct !== '' ? 'mysql_group_concat_distinct(' : 'GROUP_CONCAT(') . "$value, $separator$orderBy)";
        });
    }

    /**
     * Translate MATCH(columns) AGAINST(expr [modifier]) into mysql_match(),
     * which scores rows without a fulltext index. Arguments keep their order
     * for parameters.
     */
    private function translateMatch(string $sql): string
    {
        if (stripos($sql, 'AGAINST') === false) {
            return $sql;
        }
        $offset = 0;
        while (preg_match('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\bMATCH\s*\(/is', $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $offset = $start + 1;
            $close = $this->closingParenthesis($sql, $start + strlen($m[0][0]) - 1);
            if ($close === null || !preg_match('/\G\s*AGAINST\s*\(/i', $sql, $against, 0, $close + 1)) {
                continue;
            }
            $againstClose = $this->closingParenthesis($sql, $close + strlen($against[0]));
            if ($againstClose === null) {
                continue;
            }
            $columns = substr($sql, $start + strlen($m[0][0]), $close - $start - strlen($m[0][0]));
            $expression = substr($sql, $close + 1 + strlen($against[0]), $againstClose - $close - 1 - strlen($against[0]));
            $mode = 'natural';
            if (preg_match('/\s+(?:IN\s+(NATURAL\s+LANGUAGE|BOOLEAN)\s+MODE)?(?:\s*WITH\s+QUERY\s+EXPANSION)?\s*$/i', $expression, $modifier) && trim($modifier[0]) !== '') {
                $mode = stripos($modifier[0], 'BOOLEAN') !== false ? 'boolean' : 'natural';
                $expression = substr($expression, 0, -strlen($modifier[0]));
            }
            $replacement = "mysql_match('$mode', $columns, $expression)";
            $sql = substr($sql, 0, $start) . $replacement . substr($sql, $againstClose + 1);
            $offset = $start + strlen($replacement);
        }
        return $sql;
    }

    /**
     * Translate TRIM([BOTH|LEADING|TRAILING] [remstr] FROM str) into trim(),
     * ltrim() or rtrim(). MySQL removes remstr as a string and SQLite as a
     * set of characters, so only literals of one character are supported.
     * Backslash escapes such as "\t" are decoded as in MySQL.
     */
    private function translateTrimFrom(string $sql): string
    {
        return $this->replaceCalls($sql, 'TRIM', function (string $function, string $arguments) use ($sql): ?string {
            $at = $this->topLevelKeywords($arguments, 'FROM');
            if (!isset($at['FROM']) || !preg_match('/^\s*(?:(BOTH|LEADING|TRAILING)\b)?\s*(.*?)\s*$/is', substr($arguments, 0, $at['FROM']), $head)) {
                return null;
            }
            $characters = $head[2] === '' ? ' ' : null;
            if (preg_match('/^([\'"])(.*)\1$/s', $head[2], $literal)) {
                $characters = strtr(str_replace($literal[1] . $literal[1], $literal[1], $literal[2]), ['\\0' => "\0", '\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\Z' => "\x1A", '\\\\' => '\\', "\\'" => "'", '\\"' => '"']);
            }
            if ($characters === null || mb_strlen($characters) !== 1) {
                throw new \RuntimeException('SQLite compatibility only supports TRIM(… FROM …) with a single character: ' . $sql);
            }
            $trim = ['LEADING' => 'ltrim', 'TRAILING' => 'rtrim'][strtoupper($head[1])] ?? 'trim';
            return "$trim(" . trim(substr($arguments, $at['FROM'] + 4)) . ", '" . str_replace("'", "''", $characters) . "')";
        });
    }

    /**
     * Translate JSON_TABLE(doc, 'path[*]' COLUMNS (name type PATH 'path', …))
     * into a subquery on json_each(). Only flat column lists are supported:
     * NESTED PATH, FOR ORDINALITY and ON EMPTY/ON ERROR are left as is.
     * Column types, charsets and collations are ignored.
     */
    private function translateJsonTable(string $sql): string
    {
        return $this->replaceCalls($sql, 'JSON_TABLE', function (string $function, string $arguments): ?string {
            $at = $this->topLevelKeywords($arguments, 'COLUMNS');
            $literal = '\'(?:[^\']|\'\')*\'|"(?:[^"]|"")*"';
            if (!isset($at['COLUMNS'])
                || !preg_match('/^(.+),\s*(' . $literal . ')\s*$/s', substr($arguments, 0, $at['COLUMNS']), $head)
                || !preg_match('/^\s*COLUMNS\s*\((.*)\)\s*$/is', substr($arguments, $at['COLUMNS']), $list)
                || !preg_match('/^(\$.*?)\[\*\]$/s', substr($head[2], 1, -1), $root)
            ) {
                return null;
            }
            $columns = [];
            foreach ($this->splitSql($list[1], ',') as $column) {
                if (!preg_match('/^[`"]?(\w+)[`"]?\s+.*?\bPATH\s+(' . $literal . ')$/is', $column, $c) || preg_match('/\b(?:NESTED|ORDINALITY|ON\s+(?:EMPTY|ERROR))\b/i', $column)) {
                    return null;
                }
                $path = substr($c[2], 1, -1);
                $columns[] = ($path === '$' ? '__json_table.value' : "json_extract(__json_table.value, '" . str_replace("'", "''", $path) . "')") . " AS `$c[1]`";
            }
            return '(SELECT ' . implode(', ', $columns) . " FROM json_each($head[1], '" . str_replace("'", "''", $root[1]) . "') AS __json_table)";
        });
    }

    /**
     * Replace information_schema.TABLES with the tables and views of the
     * database. Sizes and row counts are unknown (NULL); the schema is the
     * name returned by DATABASE().
     */
    private function translateInformationSchema(string $sql): string
    {
        if (stripos($sql, 'information_schema') === false) {
            return $sql;
        }
        $schema = $this->quote($this->getDatabase() ?? 'main');
        $tables = "(SELECT 'def' AS TABLE_CATALOG, $schema AS TABLE_SCHEMA, name AS TABLE_NAME,"
            . " CASE type WHEN 'view' THEN 'VIEW' ELSE 'BASE TABLE' END AS TABLE_TYPE, NULL AS ENGINE,"
            . ' NULL AS TABLE_ROWS, NULL AS DATA_LENGTH, NULL AS INDEX_LENGTH, NULL AS TABLE_COLLATION'
            . " FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT GLOB 'sqlite_*')";
        // Quoted identifiers are matched before the protected text is skipped.
        return preg_replace('/`?\binformation_schema`?\s*\.\s*`?TABLES\b`?|(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)/i', $tables, $sql);
    }

    /**
     * Replace calls of the functions matching $names, innermost first, with
     * $replace(NAME, arguments), unless it returns null.
     */
    private function replaceCalls(string $sql, string $names, callable $replace): string
    {
        if (!preg_match('/\b(?:' . $names . ')\s*\(/i', $sql)) {
            return $sql;
        }
        $offset = 0;
        while (preg_match('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\b(' . $names . ')\s*\(/is', $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $open = $start + strlen($m[0][0]) - 1;
            $close = $this->closingParenthesis($sql, $open);
            if ($close === null) {
                break;
            }
            $arguments = $this->replaceCalls(substr($sql, $open + 1, $close - $open - 1), $names, $replace);
            $replacement = $replace(strtoupper($m[1][0]), $arguments) ?? substr($sql, $start, $open + 1 - $start) . $arguments . ')';
            $sql = substr($sql, 0, $start) . $replacement . substr($sql, $close + 1);
            $offset = $start + strlen($replacement);
        }
        return $sql;
    }

    /**
     * Offsets of the first occurrence of each keyword outside parentheses and
     * quoted text, keyed by its first word in upper case.
     *
     * @return int[]
     */
    private function topLevelKeywords(string $sql, string $keywords): array
    {
        preg_match_all('/' . self::SQL_PROTECTED . '|[()]|\b(?:' . $keywords . ')\b/is', $sql, $tokens, PREG_OFFSET_CAPTURE);
        $depth = 0;
        $at = [];
        foreach ($tokens[0] as [$token, $offset]) {
            if ($token === '(') {
                $depth++;
            } elseif ($token === ')') {
                $depth--;
            } elseif ($depth === 0 && ctype_alpha($token[0])) {
                $at[strtoupper(strtok($token, " \t\r\n"))] ??= $offset;
            }
        }
        return $at;
    }

    /**
     * Remove quoted text and comments, e.g. to look for parameters.
     */
    private function unquoted(string $sql): string
    {
        return preg_replace('/' . self::SQL_PROTECTED . '/s', '', $sql);
    }

    /**
     * Find the parenthesis closing the one at $open, skipping quoted text.
     */
    private function closingParenthesis(string $sql, int $open): ?int
    {
        preg_match_all('/' . self::SQL_PROTECTED . '|[()]/s', $sql, $tokens, PREG_OFFSET_CAPTURE, $open);
        $depth = 0;
        foreach ($tokens[0] as [$token, $offset]) {
            if ($token === '(') {
                $depth++;
            } elseif ($token === ')' && --$depth === 0) {
                return $offset;
            }
        }
        return null;
    }

    /**
     * Translate INSERT … ON DUPLICATE KEY UPDATE into an SQLite upsert.
     *
     * Without a conflict target, ON CONFLICT DO UPDATE fires on any unique
     * key, as in MySQL. VALUES(col) becomes excluded.col and assigned columns
     * lose their table qualifier. An INSERT … SELECT gets its SELECT wrapped
     * and a WHERE clause, which SQLite requires to parse the upsert.
     * Expressions referencing tables of the SELECT are not supported, as
     * SQLite only exposes the target and excluded rows.
     */
    private function translateUpsert(string $sql): string
    {
        if (stripos($sql, 'DUPLICATE') === false
            || !preg_match('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/is', $sql, $m, PREG_OFFSET_CAPTURE)
        ) {
            return $sql;
        }
        $insert = rtrim(substr($sql, 0, $m[0][1]));
        $update = rtrim(substr($sql, $m[0][1] + strlen($m[0][0])), " \t\n\r;");
        $update = preg_replace('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\bVALUES\s*\(\s*([`"]?\w+[`"]?)\s*\)/is', 'excluded.$1', $update);
        $assignments = array_map(function (string $assignment): string {
            return preg_replace('/^\s*[`"]?\w+[`"]?\s*\.\s*([`"]?\w+[`"]?\s*=)/', '$1', $assignment);
        }, $this->splitSql($update, ','));
        if (preg_match('/^(\s*INSERT\s+(?:OR\s+\w+\s+)?INTO\s+[`"]?\w+[`"]?\s*(?:\([^()]*\))?\s*)(SELECT\b.*)$/is', $insert, $select)) {
            $insert = $select[1] . 'SELECT * FROM (' . $select[2] . ') WHERE true';
        }
        return $insert . ' ON CONFLICT DO UPDATE SET ' . implode(', ', array_map('trim', $assignments));
    }

    /**
     * Translate MySQL's multiple-table DELETE target FROM … JOIN … into a
     * DELETE of the target rows selected by the same FROM clause. Deleting
     * from several tables at once is not supported.
     */
    private function translateDeleteJoin(string $sql): string
    {
        if (!preg_match('/^(\s*)DELETE\s+(?!FROM\b)[`"]?(\w+)[`"]?\s+FROM\s+(.+?)[\s;]*$/is', $sql, $m)) {
            return $sql;
        }
        [, $indent, $target, $from] = $m;
        $table = $target;
        $keywords = ['ON', 'USING', 'WHERE', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'CROSS', 'NATURAL', 'STRAIGHT_JOIN', 'ORDER', 'LIMIT'];
        // Skip strings and comments, not quoted identifiers.
        preg_match_all('/(?:\'(?:[^\']|\'\')*\'|--[^\r\n]*|\/\*.*?\*\/)(*SKIP)(*F)|(?:^|\bJOIN\s+|,\s*)[`"]?(\w+)[`"]?(?:\s+(?:AS\s+)?[`"]?(\w+)[`"]?)?/is', $from, $refs, PREG_SET_ORDER);
        foreach ($refs as $ref) {
            $alias = isset($ref[2]) && !in_array(strtoupper($ref[2]), $keywords, true) ? $ref[2] : null;
            if ($alias !== null && strcasecmp($alias, $target) === 0) {
                $table = $ref[1];
                break;
            }
        }
        return "{$indent}DELETE FROM `{$table}` WHERE rowid IN (SELECT `{$target}`.rowid FROM {$from})";
    }

    /**
     * Translate MySQL's multiple-table UPDATE target … JOIN … SET … into an
     * SQLite UPDATE … FROM a subquery made of the same joins and WHERE, which
     * selects the target rowid and the assigned values. SQLite does not let
     * joins reference the target table; inside the subquery they can.
     * Only columns of the target table can be assigned.
     */
    private function translateUpdateJoin(string $sql): string
    {
        $id = '[`"]?(\w+)[`"]?';
        $join = '(?:(?:INNER|CROSS|LEFT(?:\s+OUTER)?)\s+)?JOIN\b';
        if (!preg_match('/^(\s*)UPDATE\s+' . $id . '(?:\s+(?:AS\s+)?(?!(?:INNER|CROSS|LEFT|JOIN|SET)\b)' . $id . ')?\s+(' . $join . '.+?)[\s;]*$/is', $sql, $m)) {
            return $this->unqualifyUpdateTargets($sql);
        }
        [, $indent, $table, $alias, $rest] = $m;
        $target = $alias !== '' ? $alias : $table;
        $at = $this->topLevelKeywords($rest, 'SET|WHERE');
        if (!isset($at['SET'])) {
            return $sql;
        }
        $joins = substr($rest, 0, $at['SET']);
        $end = isset($at['WHERE']) && $at['WHERE'] > $at['SET'] ? $at['WHERE'] : strlen($rest);
        $assignments = substr($rest, $at['SET'] + 3, $end - $at['SET'] - 3);
        $where = substr($rest, $end);
        if (strpos($this->unquoted($joins), '?') !== false && strpos($this->unquoted($assignments), '?') !== false) {
            throw new \RuntimeException('SQLite compatibility cannot reorder positional parameters of an UPDATE … JOIN: ' . $sql);
        }
        $set = [];
        $values = ["`{$target}`.rowid AS __rowid"];
        foreach ($this->splitSql($assignments, ',') as $i => $assignment) {
            if (!preg_match('/^(?:[`"]?(\w+)[`"]?\s*\.\s*)?[`"]?(\w+)[`"]?\s*=(.+)$/s', $assignment, $a)
                || ($a[1] !== '' && strcasecmp($a[1], $target) !== 0)
            ) {
                throw new \RuntimeException('SQLite can only update columns of the target table of an UPDATE … JOIN: ' . $sql);
            }
            $set[] = "`{$a[2]}` = __update_join.__value{$i}";
            $values[] = trim($a[3]) . " AS __value{$i}";
        }
        $from = "`{$table}`" . ($alias !== '' ? " AS `{$alias}`" : '');
        return "{$indent}UPDATE `{$table}` SET " . implode(', ', $set)
            . ' FROM (SELECT ' . implode(', ', $values) . " FROM {$from} " . trim($joins) . ($where !== '' ? ' ' . $where : '') . ') AS __update_join'
            . " WHERE `{$table}`.rowid = __update_join.__rowid";
    }

    /**
     * Remove the table or alias qualifier of the columns assigned by a
     * single-table UPDATE, e.g. UPDATE value AS v SET v.value = …, which
     * SQLite refuses.
     */
    private function unqualifyUpdateTargets(string $sql): string
    {
        if (!preg_match('/^(\s*UPDATE\s+[`"]?(\w+)[`"]?(?:\s+(?:AS\s+)?(?!SET\b)[`"]?(\w+)[`"]?)?\s+SET\s)(.+)$/is', $sql, $m)) {
            return $sql;
        }
        [, $head, $table, $alias, $rest] = $m;
        $qualifier = '/^[`"]?(?:' . $table . ($alias !== '' ? '|' . $alias : '') . ')[`"]?\s*\.\s*(?=[`"]?\w+[`"]?\s*=)/i';
        $at = $this->topLevelKeywords($rest, 'WHERE|ORDER\s+BY|LIMIT');
        $end = $at ? min($at) : strlen($rest);
        $assignments = $this->splitSql(substr($rest, 0, $end), ',');
        $unqualified = preg_replace($qualifier, '', $assignments);
        return $unqualified === $assignments ? $sql : $head . implode(', ', $unqualified) . ' ' . substr($rest, $end);
    }

    /**
     * Translate ALTER TABLE statements made only of ADD [UNIQUE|FULLTEXT] INDEX|KEY and
     * DROP INDEX|KEY clauses into CREATE INDEX and DROP INDEX.
     *
     * Index names are per table in MySQL but per database in SQLite, so they
     * get the table prefix used for indexes declared in CREATE TABLE. A drop
     * falls back to the bare name for indexes created by plain CREATE INDEX.
     * MySQL online DDL options (ALGORITHM, LOCK) are ignored.
     *
     * @return string[]|null Null when the statement has other clauses.
     */
    private function translateAlterIndexes(string $sql): ?array
    {
        $id = '[`"]?(\w+)[`"]?';
        if (!preg_match('/^ALTER\s+TABLE\s+' . $id . '\s+(.+)$/is', $sql, $m)) {
            return null;
        }
        $tableName = $m[1];
        $statements = [];
        foreach ($this->splitSql($m[2], ',') as $clause) {
            $clause = trim($clause);
            if (preg_match('/^(?:ALGORITHM|LOCK)\s*=?\s*\w+$/i', $clause)) {
                continue;
            }
            if (preg_match('/^ADD\s+(?:(UNIQUE)\s+|FULLTEXT\s+)?(?:INDEX|KEY)\s+(IF\s+NOT\s+EXISTS\s+)?' . $id . '\s*(\(.*\))$/is', $clause, $add)) {
                $isUnique = $add[1] !== '' ? 'UNIQUE ' : '';
                $ifNotExists = $add[2] !== '' ? 'IF NOT EXISTS ' : '';
                $statements[] = "CREATE {$isUnique}INDEX {$ifNotExists}`{$tableName}_{$add[3]}` ON `{$tableName}` " . $this->translateIndexColumns($add[4], $isUnique !== '');
            } elseif (preg_match('/^DROP\s+(?:INDEX|KEY)\s+(IF\s+EXISTS\s+)?' . $id . '$/is', $clause, $drop)) {
                $name = $tableName . '_' . $drop[2];
                if (!$this->fetchOne("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = ?", [$name])
                    && $this->fetchOne("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = ? AND tbl_name = ?", [$drop[2], $tableName])
                ) {
                    $name = $drop[2];
                }
                $statements[] = 'DROP INDEX ' . ($drop[1] !== '' ? 'IF EXISTS ' : '') . "`{$name}`";
            } else {
                return null;
            }
        }
        return $statements ?: null;
    }

    /**
     * Keep MySQL prefix uniqueness, e.g. UNIQUE (`a`(10)), with expression
     * indexes on SQLite; prefixes of non-unique indexes are dropped.
     */
    private function translateIndexColumns(string $columns, bool $isUnique): string
    {
        return preg_replace_callback('/([`"]?\w+[`"]?)\s*\((\d+)\)/', function (array $m) use ($isUnique): string {
            return $isUnique ? 'substr(' . $m[1] . ', 1, ' . $m[2] . ')' : $m[1];
        }, $columns);
    }

    /**
     * Translate ALTER TABLE statements that change columns (CHANGE, MODIFY,
     * ADD and DROP [COLUMN]) or foreign keys (ADD [CONSTRAINT name] FOREIGN
     * KEY, DROP FOREIGN KEY name), possibly mixed with index clauses.
     *
     * SQLite cannot change the columns or the foreign keys of an existing table, so the table is
     * rebuilt by DBAL (copy to a temporary table, recreate with the new keys,
     * copy back, recreate indexes). Enforcement is switched off during the
     * rebuild so dropping the old table never cascades into child tables,
     * then restored. PRAGMA foreign_keys is a no-op inside a transaction, so
     * a rebuild with enforcement on inside a transaction is refused.
     *
     * @return string[]|null Null when the statement has none of these clauses.
     */
    private function translateAlterTable(string $sql): ?array
    {
        $id = '(?:`([^`]+)`|"([^"]+)"|(\w+))';
        $unquote = function (string $name): string {
            return trim($name, " \t\n\r`\"");
        };
        $columns = function (string $list) use ($unquote): array {
            return array_map($unquote, explode(',', $list));
        };
        $action = '(CASCADE|SET\s+NULL|SET\s+DEFAULT|RESTRICT|NO\s+ACTION)';
        $notColumn = '(?!(?:INDEX|KEY|UNIQUE|PRIMARY|FOREIGN|CONSTRAINT|FULLTEXT|SPATIAL|CHECK)\b)';
        if (!preg_match('/^ALTER\s+TABLE\s+' . $id . '\s+(.+)$/is', $sql, $m)) {
            return null;
        }
        $tableName = $m[1] . $m[2] . $m[3];
        $foreignKeys = [];
        $droppedForeignKeys = [];
        $changes = [];
        $addedColumns = [];
        $removedColumns = [];
        $indexesBefore = [];
        $indexesAfter = [];
        $unsupported = [];
        foreach ($this->splitSql($m[4], ',') as $clause) {
            if (preg_match('/^(?:ALGORITHM|LOCK)\s*=?\s*\w+$/i', $clause)) {
                continue;
            }
            if (preg_match('/^(ADD|DROP)\s+(?:(?:UNIQUE|FULLTEXT)\s+)?(?:INDEX|KEY)\b/i', $clause, $index)) {
                $statements = $this->translateAlterIndexes("ALTER TABLE `$tableName` $clause");
                if ($statements === null) {
                    $unsupported[] = $clause;
                } elseif (strtoupper($index[1]) === 'DROP') {
                    array_push($indexesBefore, ...$statements);
                } else {
                    array_push($indexesAfter, ...$statements);
                }
            } elseif (preg_match('/^DROP\s+FOREIGN\s+KEY\s+' . $id . '$/is', $clause, $drop)) {
                $droppedForeignKeys[] = implode('', array_slice($drop, 1));
            } elseif (preg_match('/^ADD\s+(?:CONSTRAINT\s+' . $id . '\s+)?FOREIGN\s+KEY\s*(?:' . $id . '\s*)?\(([^)]+)\)\s*REFERENCES\s+' . $id . '\s*\(([^)]+)\)((?:\s+ON\s+(?:DELETE|UPDATE)\s+' . $action . ')*)$/is', $clause, $fk)) {
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
            } elseif (preg_match('/^(?:CHANGE\s+(?:COLUMN\s+)?' . $id . '\s+|MODIFY\s+(?:COLUMN\s+)?)' . $id . '\s+(.+)$/is', $clause, $c)) {
                $name = $c[4] . $c[5] . $c[6];
                $changes[] = [($c[1] . $c[2] . $c[3]) ?: $name, $name, $c[7]];
            } elseif (preg_match('/^ADD\s+(?:COLUMN\s+)?' . $notColumn . $id . '\s+(.+)$/is', $clause, $c)) {
                $addedColumns[] = $this->parseColumnDefinition($c[1] . $c[2] . $c[3], $c[4], true);
            } elseif (preg_match('/^DROP\s+(?:COLUMN\s+)?' . $notColumn . $id . '$/is', $clause, $c)) {
                $removedColumns[] = $c[1] . $c[2] . $c[3];
            } else {
                $unsupported[] = $clause;
            }
        }
        if (!$foreignKeys && !$droppedForeignKeys && !$changes && !$addedColumns && !$removedColumns
            && !preg_grep('/\bFOREIGN\s+KEY\b/i', $unsupported)
        ) {
            return null;
        }
        if ($unsupported) {
            throw new \RuntimeException('Unsupported ALTER TABLE clause on SQLite: ' . implode(', ', $unsupported));
        }

        $enforced = (bool) $this->fetchOne('PRAGMA foreign_keys');
        if ($enforced && $this->isTransactionActive()) {
            throw new \RuntimeException('SQLite cannot rebuild a table to change its foreign keys inside a transaction while foreign keys are enforced.');
        }
        // DBAL 2 reports no foreign key support for SQLite, so listTableDetails()
        // omits the existing keys; add them or the rebuild would drop them.
        $schemaManager = $this->getSchemaManager();
        $table = $schemaManager->listTableDetails($tableName);
        $fromTable = new Table($tableName, $table->getColumns(), $table->getIndexes(), $schemaManager->listTableForeignKeys($tableName), 0, $table->getOptions());
        foreach ($droppedForeignKeys as $name) {
            // MySQL refuses an unknown key; DBAL would silently keep the table as is.
            if (!$fromTable->hasForeignKey($name)) {
                throw new \RuntimeException("Cannot drop foreign key $name: it does not exist on table $tableName.");
            }
        }
        // Table adds implicit indexes for foreign keys; they do not exist here.
        foreach (array_diff_key($fromTable->getIndexes(), $table->getIndexes()) as $name => $index) {
            $fromTable->dropIndex($name);
        }
        // DBAL ignores the indexes of UNIQUE constraints (sqlite_autoindex_*),
        // so the rebuild would lose them; recreate them as unique indexes.
        $platform = $this->getDatabasePlatform();
        $nothingToDrop = [];
        foreach ($this->fetchFirstColumn("SELECT name FROM pragma_index_list(?) WHERE origin = 'u'", [$tableName]) as $autoindex) {
            $uniqueColumns = $this->fetchFirstColumn('SELECT name FROM pragma_index_info(?) ORDER BY seqno', [$autoindex]);
            $name = $this->generateIndexName($tableName, implode('_', $uniqueColumns), true);
            $fromTable->addUniqueIndex($uniqueColumns, $name);
            $nothingToDrop[] = $platform->getDropIndexSQL($fromTable->getIndex($name), $tableName);
        }
        $diff = new TableDiff($tableName, [], [], [], [], [], [], $fromTable);
        $diff->addedForeignKeys = $foreignKeys;
        $diff->removedForeignKeys = $droppedForeignKeys;
        foreach ($addedColumns as $column) {
            $diff->addedColumns[$column->getName()] = $column;
        }
        foreach (array_merge($removedColumns, array_column($changes, 0)) as $name) {
            if (!$fromTable->hasColumn($name)) {
                throw new \RuntimeException("Unknown column $name in table $tableName.");
            }
        }
        foreach ($removedColumns as $name) {
            $diff->removedColumns[strtolower($name)] = $fromTable->getColumn($name);
        }
        foreach ($changes as [$oldName, $name, $definition]) {
            $column = $this->parseColumnDefinition($name, $definition);
            $fromColumn = $fromTable->getColumn($oldName);
            $diff->changedColumns[strtolower($oldName)] = new ColumnDiff($oldName, $column, (new Comparator())->diffColumn($fromColumn, $column), $fromColumn);
        }
        $statements = array_values(array_diff($platform->getAlterTableSQL($diff), $nothingToDrop));
        $statements = array_merge($indexesBefore, $statements, $indexesAfter);
        return $enforced
            ? array_merge(['PRAGMA foreign_keys = OFF'], $statements, ['PRAGMA foreign_keys = ON'])
            : $statements;
    }

    /**
     * Build the DBAL column of a MySQL column definition, such as
     * "VARCHAR(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `id`".
     * SQLite has no charsets, so CHARACTER SET and COLLATE are ignored, as are
     * AFTER and FIRST: the rebuild moves changed columns to the end.
     *
     * MySQL fills a new NOT NULL column without default with the implicit
     * default of its type; SQLite needs it as an actual default.
     */
    private function parseColumnDefinition(string $name, string $definition, bool $isNew = false): Column
    {
        if (!preg_match('/^(\w+)\s*(?:\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\))?(?:\s+(?:UNSIGNED|SIGNED|ZEROFILL)\b)*(.*)$/is', trim($definition), $m)) {
            throw new \RuntimeException("Unsupported column definition on SQLite: $definition");
        }
        [, $mysqlType, $length, $scale, $rest] = $m;
        $mysqlType = strtolower($mysqlType);
        $types = [
            'boolean' => ['bool', 'boolean'],
            'smallint' => ['tinyint', 'smallint'],
            'integer' => ['mediumint', 'int', 'integer', 'year'],
            'bigint' => ['bigint'],
            'string' => ['char', 'varchar'],
            'text' => ['tinytext', 'text', 'mediumtext', 'longtext'],
            'json' => ['json'],
            'date' => ['date'],
            'datetime' => ['datetime', 'timestamp'],
            'time' => ['time'],
            'decimal' => ['decimal', 'dec', 'numeric'],
            'float' => ['float', 'double', 'real'],
            'binary' => ['binary', 'varbinary'],
            'blob' => ['tinyblob', 'blob', 'mediumblob', 'longblob'],
        ];
        $type = $mysqlType === 'tinyint' && $length === '1' ? 'boolean' : null;
        foreach ($types as $dbalType => $mysqlTypes) {
            $type = $type ?? (in_array($mysqlType, $mysqlTypes, true) ? $dbalType : null);
        }
        if ($type === null) {
            throw new \RuntimeException("Unsupported column type on SQLite: $definition");
        }
        $options = ['notnull' => false, 'fixed' => $mysqlType === 'char'];
        if ($length !== '') {
            $options += $type === 'decimal' ? ['precision' => (int) $length, 'scale' => (int) $scale] : ['length' => (int) $length];
        }
        $literal = '\'(?:[^\']|\'\')*\'|"(?:[^"]|"")*"';
        $unquote = function (string $value): string {
            return str_replace($value[0] . $value[0], $value[0], substr($value, 1, -1));
        };
        while (($rest = ltrim($rest)) !== '') {
            if (preg_match('/^NOT\s+NULL\b/i', $rest, $o)) {
                $options['notnull'] = true;
            } elseif (preg_match('/^NULL\b/i', $rest, $o)) {
                $options['notnull'] = false;
            } elseif (preg_match('/^DEFAULT\s+(?:(' . $literal . ')|([-+]?\d+(?:\.\d+)?)|(NULL|TRUE|FALSE|CURRENT_TIMESTAMP)(?:\s*\(\s*\d*\s*\))?)/i', $rest, $o)) {
                $keyword = strtoupper($o[3] ?? '');
                $options['default'] = $o[1] !== '' ? $unquote($o[1])
                    : ($o[2] !== '' ? $o[2] : ['NULL' => null, 'TRUE' => '1', 'FALSE' => '0', 'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP'][$keyword]);
            } elseif (preg_match('/^AUTO_INCREMENT\b/i', $rest, $o)) {
                $options['autoincrement'] = true;
            } elseif (preg_match('/^COMMENT\s+(' . $literal . ')/i', $rest, $o)) {
                $comment = $unquote($o[1]);
                // DBAL adds the type comment itself.
                if (preg_match('/^\(DC2Type:(\w+)\)$/', $comment, $hint) && Type::hasType($hint[1])) {
                    $type = $hint[1];
                } else {
                    $options['comment'] = $comment;
                }
            } elseif (!preg_match('/^(?:(?:COLLATE|CHARACTER\s+SET|CHARSET)\s*=?\s*(?:' . $literal . '|`\w+`|\w+)|AFTER\s+(?:`[^`]+`|"[^"]+"|\w+)|FIRST\b)/i', $rest, $o)) {
                throw new \RuntimeException("Unsupported column definition on SQLite: $definition");
            }
            $rest = substr($rest, strlen($o[0]));
        }
        if ($isNew && $options['notnull'] && !isset($options['default']) && empty($options['autoincrement'])) {
            if (in_array($type, ['boolean', 'smallint', 'integer', 'bigint', 'decimal', 'float'], true)) {
                $options['default'] = '0';
            } elseif (in_array($type, ['string', 'text', 'json'], true)) {
                $options['default'] = '';
            }
        }
        return new Column($name, Type::getType($type), $options);
    }

    private function translateShowIndex(string $table, ?string $where): string
    {
        if (!$this->fetchOne("SELECT 1 FROM sqlite_master WHERE type='table' AND name=? COLLATE NOCASE UNION ALL SELECT 1 FROM sqlite_temp_master WHERE type='table' AND name=? COLLATE NOCASE", [$table, $table])) {
            throw new \RuntimeException('Cannot show indexes: table does not exist: ' . $table);
        }
        // Integer rowid primary keys have no entry in pragma_index_list.
        // Report the MySQL name of indexes that got the table prefix (see
        // translateAlterIndexes()), except an index named after its column.
        $prefix = strlen($table) + 2;
        $sql = <<<SQL
WITH index_columns AS (
    SELECT CASE WHEN il.origin = 'pk' THEN 'PRIMARY'
            WHEN substr(il.name, 1, $prefix - 1) = '{$table}_' COLLATE NOCASE
                AND NOT EXISTS (SELECT 1 FROM pragma_index_info(il.name) WHERE name = il.name)
            THEN substr(il.name, $prefix) ELSE il.name END AS index_name,
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
        $closeParen = $openParen === false ? null : $this->closingParenthesis($sql, $openParen);
        if ($closeParen === null) {
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

            // ponytail: a FULLTEXT index becomes a plain index, which keeps
            // SHOW INDEX and DROP INDEX working but does not speed up
            // MATCH() (see mysql_match()); use FTS5 if searches get slow.
            $line = preg_replace('/^\s*FULLTEXT\s+(?:(?:KEY|INDEX)\b\s*)?/i', 'INDEX ', $line);

            // Extract KEY/INDEX into separate CREATE INDEX statements. The index
            // name is optional in MySQL, e.g. "UNIQUE INDEX (`a`, `b`)" or
            // "KEY (`a`)"; SQLite forbids inline (non-primary) indexes, so both
            // named and unnamed forms must become standalone CREATE INDEX.
            if (preg_match('/^\s*(?:(UNIQUE)\s+)?(?:KEY|INDEX)\s*(?:[`"\']?(\w+)[`"\']?\s*)?(\(.*\))/i', $line, $km)) {
                $isUnique = ($km[1] ?? '') !== '' ? 'UNIQUE ' : '';
                $indexCols = $this->translateIndexColumns($km[3], $isUnique !== '');
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
        $suffix = preg_replace('/(?:' . self::SQL_PROTECTED . ')(*SKIP)(*F)|\s*(?:(?:ENGINE|(?:DEFAULT\s+)?(?:CHARSET|CHARACTER\s+SET)|COLLATE)\s*=?\s*(?:\w+|`\w+`|"\w+"|\'\w+\')|COMMENT\s*=?\s*\'(?:[^\']|\'\')*\')/is', '', $suffix);
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
        $aggregate = function (string $name, callable $step, callable $finalize, int $numArgs) use ($pdo): void {
            if (method_exists($pdo, 'createAggregate')) {
                $pdo->createAggregate($name, $step, $finalize, $numArgs);
            } elseif (method_exists($pdo, 'sqliteCreateAggregate')) {
                $pdo->sqliteCreateAggregate($name, $step, $finalize, $numArgs);
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
        $create('elt', function ($index, ...$strings) {
            return is_numeric($index) ? ($strings[(int) $index - 1] ?? null) : null;
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
            return @preg_match($this->mysqlRegex($pattern), (string) $value) === 1 ? 1 : 0;
        }, 2);
        $create('regexp_replace', function ($value, $pattern, $replacement) {
            if ($value === null || $pattern === null || $replacement === null) {
                return null;
            }
            return preg_replace($this->mysqlRegex($pattern), (string) $replacement, (string) $value);
        }, 3);
        $create('regexp_substr', function ($value, $pattern) {
            if ($value === null || $pattern === null) {
                return null;
            }
            return preg_match($this->mysqlRegex($pattern), (string) $value, $m) === 1 ? $m[0] : null;
        }, 2);
        $create('substring_index', function ($value, $delimiter, $count) {
            // MySQL: the part before the count-th delimiter, counted from the
            // right when count is negative.
            if ($value === null || $delimiter === null || $count === null) {
                return null;
            }
            $count = (int) $count;
            if ($count === 0 || (string) $delimiter === '') {
                return '';
            }
            $parts = explode((string) $delimiter, (string) $value);
            return implode((string) $delimiter, $count > 0 ? array_slice($parts, 0, $count) : array_slice($parts, $count));
        }, 3);
        $create('rand', function () {
            // ponytail: a seed argument is ignored, add seeding if a module relies on repeatable order.
            return mt_rand() / (mt_getrandmax() + 1);
        }, -1);
        foreach (['year' => 1, 'month' => 2, 'day' => 3, 'hour' => 4, 'minute' => 5, 'second' => 6] as $name => $part) {
            $create($name, function ($datetime) use ($part) {
                // MySQL returns NULL for a value that is not a date.
                if ($datetime === null || !preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/', (string) $datetime, $m)) {
                    return null;
                }
                return (int) ($m[$part] ?? 0);
            }, 1);
        }
        $create('last_day', function ($datetime) {
            $timestamp = $datetime === null ? false : strtotime((string) $datetime);
            return $timestamp === false ? null : date('Y-m-t', $timestamp);
        }, 1);
        // Target of DATE_ADD() and DATE_SUB() (see translateDateArithmetic()).
        $create('mysql_date_add', function ($datetime, $amount, $unit) {
            if ($datetime === null || !is_numeric($amount)
                || !preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?/', (string) $datetime, $m)
            ) {
                return null;
            }
            $date = new \DateTimeImmutable($m[0], new \DateTimeZone('UTC'));
            $amount = (int) round($amount);
            $months = ['MONTH' => 1, 'QUARTER' => 3, 'YEAR' => 12][$unit] ?? 0;
            if ($months) {
                // MySQL keeps the day within the target month.
                $target = $date->modify('first day of this month')->modify(($amount * $months) . ' months');
                $date = $target->setDate((int) $target->format('Y'), (int) $target->format('n'), min((int) $date->format('j'), (int) $target->format('t')));
            } else {
                $date = $unit === 'WEEK' ? $date->modify(($amount * 7) . ' days') : $date->modify("$amount $unit");
            }
            // A date stays a date unless a time unit is added.
            return strlen($m[0]) === 10 && in_array($unit, ['DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR'], true)
                ? $date->format('Y-m-d')
                : $date->format('Y-m-d H:i:s');
        }, 3);
        // Modules read it to tell MySQL from MariaDB.
        $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $create('version', function () use ($version) {
            return $version;
        }, 0);
        $database = $this->getDatabase() ?? 'main';
        $create('database', function () use ($database) {
            return $database;
        }, 0);
        // Target of MATCH() … AGAINST() (see translateMatch()). The score is
        // the number of matched terms: no stopwords, minimum word length or
        // query expansion. Boolean mode supports +, -, "phrases" and prefix*,
        // not grouping.
        $create('mysql_match', function ($mode, ...$args) {
            $query = (string) array_pop($args);
            $text = implode(' ', array_filter($args, 'is_scalar'));
            $word = '[\p{L}\p{N}_]';
            preg_match_all('/([+\-~<>]?)(?:"([^"]*)"|(' . $word . '+)(\*?))/u', $query, $terms, PREG_SET_ORDER);
            $score = 0;
            foreach ($terms as $term) {
                $words = preg_split('/[^\p{L}\p{N}_]+/u', ($term[2] ?? '') !== '' ? $term[2] : ($term[3] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
                if (!$words) {
                    continue;
                }
                $pattern = '/(?<!' . $word . ')' . implode('[^\p{L}\p{N}_]+', array_map(function ($w) {
                    return preg_quote($w, '/');
                }, $words)) . (($term[4] ?? '') === '*' ? '' : '(?!' . $word . ')') . '/iu';
                $found = preg_match($pattern, $text) === 1;
                $operator = $mode === 'boolean' ? $term[1] : '';
                if ($operator === '-' ? $found : ($operator === '+' && !$found)) {
                    return 0;
                }
                $score += $found && $operator !== '-' ? 1 : 0;
            }
            return $score;
        }, -1);
        // DISTINCT with a separator, which SQLite's group_concat() refuses
        // (see translateGroupConcat()). Values keep the ORDER BY order.
        $aggregate('mysql_group_concat_distinct', function ($context, $row, $value, $separator) {
            $context ??= ['separator' => (string) $separator, 'values' => []];
            if ($value !== null) {
                $context['values'][(string) $value] = true;
            }
            return $context;
        }, function ($context) {
            return empty($context['values']) ? null : implode($context['separator'], array_keys($context['values']));
        }, 2);
        $create('str_to_date', function ($value, $format) {
            if ($value === null || $format === null) {
                return null;
            }
            $map = ['%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n', '%d' => 'd', '%e' => 'j', '%M' => 'F', '%b' => 'M',
                '%H' => 'H', '%k' => 'G', '%h' => 'h', '%I' => 'h', '%l' => 'g', '%i' => 'i', '%s' => 's', '%S' => 's',
                '%p' => 'A', '%f' => 'u', '%T' => 'H:i:s', '%%' => '%'];
            $date = \DateTime::createFromFormat('!' . strtr((string) $format, $map), (string) $value);
            $errors = \DateTime::getLastErrors();
            if ($date === false || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
                return null;
            }
            // MySQL returns a DATE unless the format has time parts.
            return $date->format(preg_match('/%[HkhIlisSpfT]/', (string) $format) ? 'Y-m-d H:i:s' : 'Y-m-d');
        }, 2);
        $create('crc32', function ($value) {
            // PHP passes integers to SQLite as 32-bit signed values; a real
            // keeps the unsigned checksum exact.
            return $value === null ? null : (float) crc32((string) $value);
        }, 1);
        $create('sha1', function ($value) {
            return $value === null ? null : sha1((string) $value);
        }, 1);
        $create('sha2', function ($value, $length) {
            $length = (int) $length ?: 256;
            if ($value === null || !in_array($length, [224, 256, 384, 512], true)) {
                return null;
            }
            return hash('sha' . $length, (string) $value);
        }, 2);
        // Paths of the strings matching a LIKE pattern, as MySQL encodes them.
        $create('json_search', function ($document, $mode, $search, $escape = null, $path = null) {
            $data = json_decode((string) $document, true);
            if ($document === null || $search === null || json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            $escape = $escape === null || $escape === '' ? '\\' : (string) $escape;
            $pattern = '/^' . preg_replace_callback('/' . preg_quote($escape, '/') . '(.)|(%)|(_)|(.)/su', function ($m) {
                return isset($m[4]) ? preg_quote($m[4], '/') : (isset($m[3]) ? '.' : (isset($m[2]) && $m[2] !== '' ? '.*' : preg_quote($m[1], '/')));
            }, (string) $search) . '$/su';
            $found = [];
            $walk = function ($node, string $at) use (&$walk, &$found, $pattern): void {
                if (is_array($node)) {
                    $isList = array_keys($node) === range(0, count($node) - 1);
                    foreach ($node as $key => $child) {
                        $walk($child, $at . ($isList ? "[$key]" : (preg_match('/^[A-Za-z_]\w*$/', (string) $key) ? ".$key" : '.' . json_encode((string) $key))));
                    }
                } elseif (is_string($node) && preg_match($pattern, $node)) {
                    $found[] = $at;
                }
            };
            $walk($data, '$');
            if ($path !== null) {
                $found = array_values(array_filter($found, function ($p) use ($path) {
                    return strpos($p, (string) $path) === 0;
                }));
            }
            if (!$found) {
                return null;
            }
            return strtolower((string) $mode) === 'all' && count($found) > 1
                ? '[' . implode(', ', array_map('json_encode', $found)) . ']'
                : json_encode($found[0]);
        }, -1);
        $create('json_unquote', function ($value) {
            if ($value === null) {
                return null;
            }
            $decoded = json_decode((string) $value);
            return is_string($decoded) ? $decoded : (string) $value;
        }, 1);
    }

    /**
     * Build a PCRE pattern from a MySQL regular expression. Omeka tables use
     * case-insensitive collations, so MySQL matches them case-insensitively.
     */
    private function mysqlRegex($pattern): string
    {
        return '/' . str_replace('/', '\\/', (string) $pattern) . '/iu';
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
