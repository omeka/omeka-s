<?php
/**
 * Copyright (C) 2015 Derek J. Lambert
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CrEOF\Geo\WKT;

use CrEOF\Geo\WKT\Exception\UnexpectedValueException;

/**
 * Parse WKT/EWKT spatial object strings
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class Parser
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $input;

    /**
     * @var int
     */
    private $srid;

    /**
     * @var string
     */
    private $dimension;

    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @param string|null $input
     */
    public function __construct($input = null)
    {
        $this->lexer = new Lexer();

        if (null !== $input) {
            $this->input = $input;
        }
    }

    /**
     * @param string|null $input
     *
     * @return array
     */
    public function parse($input = null)
    {
        if (null !== $input) {
            $this->input = $input;
        }

        $this->lexer->setInput($this->input);
        $this->lexer->moveNext();

        $this->srid      = null;
        $this->dimension = null;

        if ($this->lexer->isNextToken(Lexer::T_SRID)) {
            $this->srid = $this->srid();
        }

        $geometry              = $this->geometry();
        $geometry['srid']      = $this->srid;
        $geometry['dimension'] = '' === $this->dimension ? null : $this->dimension;

        return $geometry;
    }

    /**
     * Match SRID in EWKT object
     *
     * @return int
     */
    protected function srid()
    {
        $this->match(Lexer::T_SRID);
        $this->match(Lexer::T_EQUALS);
        $this->match(Lexer::T_INTEGER);

        $srid = $this->lexer->value();

        $this->match(Lexer::T_SEMICOLON);

        return $srid;
    }

    /**
     * Match spatial data type
     *
     * @return string
     */
    protected function type()
    {
        $this->match(Lexer::T_TYPE);

        return $this->lexer->value();
    }

    /**
     * Match spatial geometry object
     *
     * @return array
     */
    protected function geometry()
    {
        $type       = $this->type();
        $this->type = $type;

        if ($this->lexer->isNextTokenAny(array(Lexer::T_Z, Lexer::T_M, Lexer::T_ZM))) {
            $this->match($this->lexer->lookahead['type']);

            $this->dimension = $this->lexer->value();
        }

        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $value = $this->$type();

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return array(
            'type'  => $type,
            'value' => $value
        );
    }

    /**
     * Match a coordinate pair
     *
     * @return array
     */
    protected function point()
    {
        if (null !== $this->dimension) {
            return $this->coordinates(2 + strlen($this->dimension));
        }

        $values = $this->coordinates(2);

        for ($i = 3; $i <= 4 && $this->lexer->isNextTokenAny(array(Lexer::T_FLOAT, Lexer::T_INTEGER)); $i++) {
            $values[] = $this->coordinate();
        }

        switch (count($values)) {
            case 2:
                $this->dimension = '';
                break;
            case 3:
                $this->dimension = 'Z';
                break;
            case 4:
                $this->dimension = 'ZM';
                break;
        }

        return $values;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    protected function coordinates($count)
    {
        $values = array();

        for ($i = 1; $i <= $count; $i++) {
            $values[] = $this->coordinate();
        }

        return $values;
    }

    /**
     * Match a number and optional exponent
     *
     * @return int|float
     */
    protected function coordinate()
    {
        $this->match(($this->lexer->isNextToken(Lexer::T_FLOAT) ? Lexer::T_FLOAT : Lexer::T_INTEGER));

        return $this->lexer->value();
    }

    /**
     * Match LINESTRING value
     *
     * @return array[]
     */
    protected function lineString()
    {
        return $this->pointList();
    }

    /**
     * Match POLYGON value
     *
     * @return array[]
     */
    protected function polygon()
    {
        return $this->pointLists();
    }

    /**
     * Match a list of coordinates
     *
     * @return array[]
     */
    protected function pointList()
    {
        $points = array($this->point());

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $points[] = $this->point();
        }

        return $points;
    }

    /**
     * Match nested lists of coordinates
     *
     * @return array[]
     */
    protected function pointLists()
    {
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $pointLists = array($this->pointList());

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->match(Lexer::T_OPEN_PARENTHESIS);

            $pointLists[] = $this->pointList();

            $this->match(Lexer::T_CLOSE_PARENTHESIS);
        }

        return $pointLists;
    }

    /**
     * Match MULTIPOLYGON value
     *
     * @return array[]
     */
    protected function multiPolygon()
    {
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $polygons = array($this->polygon());

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->match(Lexer::T_OPEN_PARENTHESIS);

            $polygons[] = $this->polygon();

            $this->match(Lexer::T_CLOSE_PARENTHESIS);
        }

        return $polygons;
    }

    /**
     * Match MULTIPOINT value
     *
     * @return array[]
     */
    protected function multiPoint()
    {
        return $this->pointList();
    }

    /**
     * Match MULTILINESTRING value
     *
     * @return array[]
     */
    protected function multiLineString()
    {
        return $this->pointLists();
    }

    /**
     * Match GEOMETRYCOLLECTION value
     *
     * @return array[]
     */
    protected function geometryCollection()
    {
        $collection = array($this->geometry());

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $collection[] = $this->geometry();
        }

        return $collection;
    }

    /**
     * Match token at current position in input
     *
     * @param $token
     */
    protected function match($token)
    {
        $lookaheadType = $this->lexer->lookahead['type'];

        if ($lookaheadType !== $token && ($token !== Lexer::T_TYPE || $lookaheadType <= Lexer::T_TYPE)) {
            throw $this->syntaxError($this->lexer->getLiteral($token));
        }

        $this->lexer->moveNext();
    }

    /**
     * Create exception with descriptive error message
     *
     * @param string $expected
     *
     * @return UnexpectedValueException
     */
    private function syntaxError($expected)
    {
        $expected = sprintf('Expected %s, got', $expected);
        $token    = $this->lexer->lookahead;
        $found    = null === $this->lexer->lookahead ? 'end of string.' : sprintf('"%s"', $token['value']);
        $message  = sprintf(
            '[Syntax Error] line 0, col %d: Error: %s %s in value "%s"',
            isset($token['position']) ? $token['position'] : '-1',
            $expected,
            $found,
            $this->input
        );

        return new UnexpectedValueException($message);
    }
}
