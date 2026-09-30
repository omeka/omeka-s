<?php
namespace Omeka\Db\Query;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Query\SqlWalker;
use DoctrineExtensions\Query\Mysql\GroupConcat as MysqlGroupConcat;

/**
 * Dialect-aware GROUP_CONCAT DQL function.
 *
 * MySQL syntax:  GROUP_CONCAT([DISTINCT] x [ORDER BY ...] [SEPARATOR 'sep'])
 * SQLite syntax: GROUP_CONCAT([DISTINCT] x[, 'sep'] [ORDER BY ...])
 *
 * The parent class always emits the MySQL form; SEPARATOR is a MySQL-only
 * keyword that SQLite rejects as a syntax error, so on SQLite the separator
 * is emitted as a second argument instead.
 */
class GroupConcat extends MysqlGroupConcat
{
    public function getSql(SqlWalker $sqlWalker): string
    {
        if (!$sqlWalker->getConnection()->getDatabasePlatform() instanceof SqlitePlatform) {
            return parent::getSql($sqlWalker);
        }

        $result = 'GROUP_CONCAT(' . ($this->isDistinct ? 'DISTINCT ' : '');

        $fields = [];
        foreach ($this->pathExp as $pathExp) {
            $fields[] = $pathExp->dispatch($sqlWalker);
        }
        $result .= implode(', ', $fields);

        // SQLite rejects DISTINCT aggregates with more than one argument
        // ("DISTINCT aggregates must have exactly one argument"), so a custom
        // separator cannot be honored together with DISTINCT; fall back to the
        // default ',' separator in that case.
        if ($this->separator && !$this->isDistinct) {
            $result .= ', ' . $sqlWalker->walkStringPrimary($this->separator);
        }

        // SQLite supports ORDER BY inside aggregate calls since 3.44, placed
        // after the arguments: GROUP_CONCAT(x, 'sep' ORDER BY y).
        if ($this->orderBy) {
            $result .= ' ' . $sqlWalker->walkOrderByClause($this->orderBy);
        }

        return $result . ')';
    }
}
