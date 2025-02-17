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

namespace LongitudeOne\Spatial\ORM\Query\AST\Functions;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use LongitudeOne\Spatial\Exception\UnsupportedPlatformException;

/**
 * Abstract spatial DQL function.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * This spatial class is updated to avoid non-covered code. A lot of PostgreSQL was not tested, but that was not
 * displayed by coverage rapport. Some MySQL methods generates bug since MySQL 8.0 because their name was updated.
 *
 * It is not possible to evaluate which function is tested or not with a children containing only protected methods.
 * The new pattern consists of create an abstract method for each removed property.
 * Then, if function is not tested, the code coverage tools will report this information.
 *
 * Thus, if we analyse platform version, we can implement the getFunctionName method to return geomfromtext for
 * MySQL Version 5.7 and return st_geomfromtext for version 8.0
 *
 * @see https://stackoverflow.com/questions/60377271/why-some-spatial-functions-does-not-exists-on-my-mysql-server
 */
abstract class AbstractSpatialDQLFunction extends FunctionNode
{
    /**
     * @var Node[]
     */
    private $geometryExpression = [];

    /**
     * Get the SQL.
     *
     * @param SqlWalker $sqlWalker the SQL Walker
     *
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws Exception                    when an invalid platform was specified for this connection
     * @throws ASTException                 when node cannot dispatch SqlWalker
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $this->validatePlatform($sqlWalker->getConnection()->getDatabasePlatform());

        $arguments = [];
        foreach ($this->getGeometryExpressions() as $expression) {
            $arguments[] = $expression->dispatch($sqlWalker);
        }

        return sprintf('%s(%s)', $this->getFunctionName(), implode(', ', $arguments));
    }

    /**
     * Parse SQL.
     *
     * @param Parser $parser parser
     *
     * @throws QueryException Query exception
     */
    public function parse(Parser $parser)
    {
        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->addGeometryExpression($parser->ArithmeticPrimary());

        while (count($this->geometryExpression) < $this->getMinParameter()
            || ((count($this->geometryExpression) < $this->getMaxParameter())
                && Lexer::T_CLOSE_PARENTHESIS != $lexer->lookahead['type'])
        ) {
            $parser->match(Lexer::T_COMMA);

            $this->addGeometryExpression($parser->ArithmeticPrimary());
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * Geometry expressions fluent adder.
     *
     * @param Node $expression the node expression to add to the array of geometry expression
     *
     * @since 2.0 This function replace the protected property geomExpr which is now private.
     */
    final protected function addGeometryExpression(Node $expression): self
    {
        $this->geometryExpression[] = $expression;

        return $this;
    }

    /**
     * Geometry expressions getter.
     *
     * @since 2.0 This function replace the protected property geomExpr which is now private.
     *
     * @return Node[]
     */
    final protected function getGeometryExpressions(): array
    {
        return $this->geometryExpression;
    }

    /**
     * Test that the platform supports spatial type.
     *
     * @param AbstractPlatform $platform database spatial
     *
     * @throws UnsupportedPlatformException when platform is unsupported
     */
    protected function validatePlatform(AbstractPlatform $platform): void
    {
        $platformName = $platform->getName();

        if (!in_array($platformName, $this->getPlatforms())) {
            throw new UnsupportedPlatformException(
                sprintf('DBAL platform "%s" is not currently supported.', $platformName)
            );
        }
    }

    /**
     * Function SQL name getter.
     *
     * @since 2.0 This function replace the protected property functionName.
     */
    abstract protected function getFunctionName(): string;

    /**
     * Maximum number of parameter for the spatial function.
     *
     * @since 2.0 This function replace the protected property maxGeomExpr.
     *
     * @return int the inherited methods shall NOT return a null, but 0 when function has no parameter
     */
    abstract protected function getMaxParameter(): int;

    /**
     * Minimum number of parameter for the spatial function.
     *
     * @since 2.0 This function replace the protected property minGeomExpr.
     *
     * @return int the inherited methods shall NOT return a null, but 0 when function has no parameter
     */
    abstract protected function getMinParameter(): int;

    /**
     * Get the platforms accepted.
     *
     * @since 2.0 This function replace the protected property platforms.
     *
     * @return string[] a non-empty array of accepted platforms
     */
    abstract protected function getPlatforms(): array;
}
