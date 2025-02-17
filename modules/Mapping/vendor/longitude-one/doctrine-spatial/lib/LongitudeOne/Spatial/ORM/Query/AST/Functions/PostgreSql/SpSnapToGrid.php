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

namespace LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\AbstractSpatialDQLFunction;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\ReturnsGeometryInterface;

/**
 * ST_SnapToGrid DQL function.
 *
 * @see https://postgis.net/docs/ST_SnapToGrid.html
 *
 * Possible signatures with 2, 3, 5 or 6 parameters:
 *  geometry ST_SnapToGrid(geometry geomA, float size);
 *  geometry ST_SnapToGrid(geometry geomA, float sizeX, float sizeY);
 *  geometry ST_SnapToGrid(geometry geomA, float originX, float originY, float sizeX, float sizeY);
 *  geometry ST_SnapToGrid(geometry geomA, geometry pointOrigin, float sizeX, float sizeY, float sizeZ, float sizeM);
 *
 * @author  Dragos Protung
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org
 */
class SpSnapToGrid extends AbstractSpatialDQLFunction implements ReturnsGeometryInterface
{
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

        // 1st signature
        $this->addGeometryExpression($parser->ArithmeticFactor());
        $parser->match(Lexer::T_COMMA);
        $this->addGeometryExpression($parser->ArithmeticFactor());

        // 2nd signature
        if (Lexer::T_COMMA === $lexer->lookahead['type']) {
            $parser->match(Lexer::T_COMMA);
            $this->addGeometryExpression($parser->ArithmeticFactor());
        }

        // 3rd signature
        if (Lexer::T_COMMA === $lexer->lookahead['type']) {
            $parser->match(Lexer::T_COMMA);
            $this->addGeometryExpression($parser->ArithmeticFactor());

            $parser->match(Lexer::T_COMMA);
            $this->addGeometryExpression($parser->ArithmeticFactor());

            // 4th signature
            if (Lexer::T_COMMA === $lexer->lookahead['type']) {
                // sizeM
                $parser->match(Lexer::T_COMMA);
                $this->addGeometryExpression($parser->ArithmeticFactor());
            }
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * Function SQL name getter.
     *
     * @since 2.0 This function replace the protected property functionName.
     */
    protected function getFunctionName(): string
    {
        return 'ST_SnapToGrid';
    }

    /**
     * Maximum number of parameter for the spatial function.
     *
     * @since 2.0 This function replace the protected property maxGeomExpr.
     *
     * @return int the inherited methods shall NOT return null, but 0 when function has no parameter
     */
    protected function getMaxParameter(): int
    {
        return 6;
    }

    /**
     * Minimum number of parameter for the spatial function.
     *
     * @since 2.0 This function replace the protected property minGeomExpr.
     *
     * @return int the inherited methods shall NOT return null, but 0 when function has no parameter
     */
    protected function getMinParameter(): int
    {
        return 2;
    }

    /**
     * Get the platforms accepted.
     *
     * @since 2.0 This function replace the protected property platforms.
     *
     * @return string[] a non-empty array of accepted platforms
     */
    protected function getPlatforms(): array
    {
        return ['postgresql'];
    }
}
