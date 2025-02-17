<?php
/**
 * This file is part of the doctrine spatial extension.
 *
 * PHP 7.4 | 8.0 | 8.1
 *
 * (c) Alexandre Tranchant <alexandre.tranchant@gmail.com> 2017 - 2022
 * (c) Longitude One 2020 - 2022
 * (c) 2015 Derek J. Lambert
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace LongitudeOne\Spatial\ORM\Query;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\SqlWalker;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\ReturnsGeometryInterface;

/**
 * GeometryWalker.
 *
 * Custom DQL AST walker to return geometry objects from queries instead of strings.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 */
class GeometryWalker extends SqlWalker
{
    /**
     * Result set mapping.
     *
     * @var ResultSetMapping
     */
    protected $rsm;

    /**
     * Initializes TreeWalker with important information about the ASTs to be walked.
     *
     * @param AbstractQuery $query           the parsed Query
     * @param ParserResult  $parserResult    the result of the parsing process
     * @param array         $queryComponents the query components (symbol table)
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->rsm = $parserResult->getResultSetMapping();

        parent::__construct($query, $parserResult, $queryComponents);
    }

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param SelectExpression $selectExpression Select expression AST node
     *
     * @throws QueryException when error happend during walking into select expression
     *
     * @return string the SQL
     */
    public function walkSelectExpression($selectExpression)
    {
        $expr = $selectExpression->expression;
        $sql = parent::walkSelectExpression($selectExpression);

        if ($expr instanceof ReturnsGeometryInterface && !$selectExpression->hiddenAliasResultVariable) {
            $alias = trim(mb_strrchr($sql, ' '));
            $this->rsm->typeMappings[$alias] = 'geometry';
        }

        return $sql;
    }
}
