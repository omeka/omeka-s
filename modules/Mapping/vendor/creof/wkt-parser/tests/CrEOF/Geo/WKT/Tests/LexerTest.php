<?php
/**
 * Copyright (C) 2016 Derek J. Lambert
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

namespace CrEOF\Geo\WKT\Tests;

use CrEOF\Geo\WKT\Lexer;

/**
 * Lexer tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class LexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider tokenData
     */
    public function testTokenRecognition($value, array $expected)
    {
        $lexer = new Lexer($value);

        foreach ($expected as $token) {
            $lexer->moveNext();

            $actual = $lexer->lookahead;

            $this->assertEquals($token[0], $actual['type']);
            $this->assertEquals($token[1], $actual['value']);
            $this->assertEquals($token[2], $actual['position']);
        }
    }

    public function testTokenRecognitionReuseLexer()
    {
        $lexer = new Lexer();

        foreach ($this->tokenData() as $name => $testData) {
            $lexer->setInput($testData['value']);

            foreach ($testData['expected'] as $token) {
                $lexer->moveNext();

                $actual = $lexer->lookahead;

                $this->assertEquals($token[0], $actual['type']);
                $this->assertEquals($token[1], $actual['value']);
                $this->assertEquals($token[2], $actual['position']);
            }
        }
    }

    /**
     * @return array
     */
    public function tokenData()
    {
        return array(
            'POINT' => array(
                'value'    => 'POINT',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0)
                )
            ),
            'POINTM' => array(
                'value'    => 'POINTM',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0),
                    array(Lexer::T_M, 'M', 5)
                )
            ),
            'POINT M' => array(
                'value'    => 'POINTM',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0),
                    array(Lexer::T_M, 'M', 5)
                )
            ),
            'POINTZ' => array(
                'value'    => 'POINTZ',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0),
                    array(Lexer::T_Z, 'Z', 5)
                )
            ),
            'POINT Z' => array(
                'value'    => 'POINT Z',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0),
                    array(Lexer::T_Z, 'Z', 6)
                )
            ),
            'POINT ZM' => array(
                'value'    => 'POINT ZM',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0),
                    array(Lexer::T_ZM, 'ZM', 6)
                )
            ),
            'POINTZM' => array(
                'value'    => 'POINTZM',
                'expected' => array(
                    array(Lexer::T_POINT, 'POINT', 0),
                    array(Lexer::T_ZM, 'ZM', 5)
                )
            ),
            'LINESTRING' => array(
                'value'    => 'LINESTRING',
                'expected' => array(
                    array(Lexer::T_LINESTRING, 'LINESTRING', 0)
                )
            ),
            'LINESTRINGM' => array(
                'value'    => 'LINESTRINGM',
                'expected' => array(
                    array(Lexer::T_LINESTRING, 'LINESTRING', 0),
                    array(Lexer::T_M, 'M', 10)
                )
            ),
            'POLYGON' => array(
                'value'    => 'POLYGON',
                'expected' => array(
                    array(Lexer::T_POLYGON, 'POLYGON', 0)
                )
            ),
            'POLYGONM' => array(
                'value'    => 'POLYGONM',
                'expected' => array(
                    array(Lexer::T_POLYGON, 'POLYGON', 0),
                    array(Lexer::T_M, 'M', 7)
                )
            ),
            'MULTIPOINT' => array(
                'value'    => 'MULTIPOINT',
                'expected' => array(
                    array(Lexer::T_MULTIPOINT, 'MULTIPOINT', 0)
                )
            ),
            'MULTIPOINTM' => array(
                'value'    => 'MULTIPOINTM',
                'expected' => array(
                    array(Lexer::T_MULTIPOINT, 'MULTIPOINT', 0),
                    array(Lexer::T_M, 'M', 10)
                )
            ),
            'MULTILINESTRING' => array(
                'value'    => 'MULTILINESTRING',
                'expected' => array(
                    array(Lexer::T_MULTILINESTRING, 'MULTILINESTRING', 0)
                )
            ),
            'MULTILINESTRINGM' => array(
                'value'    => 'MULTILINESTRINGM',
                'expected' => array(
                    array(Lexer::T_MULTILINESTRING, 'MULTILINESTRING', 0),
                    array(Lexer::T_M, 'M', 15)
                )
            ),
            'MULTIPOLYGON' => array(
                'value'    => 'MULTIPOLYGON',
                'expected' => array(
                    array(Lexer::T_MULTIPOLYGON, 'MULTIPOLYGON', 0)
                )
            ),
            'MULTIPOLYGONM' => array(
                'value'    => 'MULTIPOLYGONM',
                'expected' => array(
                    array(Lexer::T_MULTIPOLYGON, 'MULTIPOLYGON', 0),
                    array(Lexer::T_M, 'M', 12)
                )
            ),
            'GEOMETRYCOLLECTION' => array(
                'value'    => 'GEOMETRYCOLLECTION',
                'expected' => array(
                    array(Lexer::T_GEOMETRYCOLLECTION, 'GEOMETRYCOLLECTION', 0)
                )
            ),
            'GEOMETRYCOLLECTIONM' => array(
                'value'    => 'GEOMETRYCOLLECTIONM',
                'expected' => array(
                    array(Lexer::T_GEOMETRYCOLLECTION, 'GEOMETRYCOLLECTION', 0),
                    array(Lexer::T_M, 'M', 18)
                )
            ),
            'COMPOUNDCURVE' => array(
                'value'    => 'COMPOUNDCURVE',
                'expected' => array(
                    array(Lexer::T_COMPOUNDCURVE, 'COMPOUNDCURVE', 0)
                )
            ),
            'COMPOUNDCURVEM' => array(
                'value'    => 'COMPOUNDCURVEM',
                'expected' => array(
                    array(Lexer::T_COMPOUNDCURVE, 'COMPOUNDCURVE', 0),
                    array(Lexer::T_M, 'M', 13)
                )
            ),
            'CIRCULARSTRING' => array(
                'value'    => 'CIRCULARSTRING',
                'expected' => array(
                    array(Lexer::T_CIRCULARSTRING, 'CIRCULARSTRING', 0)
                )
            ),
            'CIRCULARSTRINGM' => array(
                'value'    => 'CIRCULARSTRINGM',
                'expected' => array(
                    array(Lexer::T_CIRCULARSTRING, 'CIRCULARSTRING', 0),
                    array(Lexer::T_M, 'M', 14)
                )
            ),
            '35' => array(
                'value'    => '35',
                'expected' => array(
                    array(Lexer::T_INTEGER, 35, 0)
                )
            ),
            '-25' => array(
                'value'    => '-25',
                'expected' => array(
                    array(Lexer::T_INTEGER, -25, 0)
                )
            ),
            '-120.33' => array(
                'value'    => '-120.33',
                'expected' => array(
                    array(Lexer::T_FLOAT, -120.33, 0)
                )
            ),
            'SRID' => array(
                'value'    => 'SRID',
                'expected' => array(
                    array(Lexer::T_SRID, 'SRID', 0)
                )
            ),
            'SRID=4326;LINESTRING(0 0.0, 10.1 -10.025, 20.5 25.9, 53E-003 60)' => array(
                'value'    => 'SRID=4326;LINESTRING(0 0.0, 10.1 -10.025, 20.5 25.9, 53E-003 60)',
                'expected' => array(
                    array(Lexer::T_SRID, 'SRID', 0),
                    array(Lexer::T_EQUALS, '=', 4),
                    array(Lexer::T_INTEGER, 4326, 5),
                    array(Lexer::T_SEMICOLON, ';', 9),
                    array(Lexer::T_LINESTRING, 'LINESTRING', 10),
                    array(Lexer::T_OPEN_PARENTHESIS, '(', 20),
                    array(Lexer::T_INTEGER, 0, 21),
                    array(Lexer::T_FLOAT, 0, 23),
                    array(Lexer::T_COMMA, ',', 26),
                    array(Lexer::T_FLOAT, 10.1, 28),
                    array(Lexer::T_FLOAT, -10.025, 33),
                    array(Lexer::T_COMMA, ',', 40),
                    array(Lexer::T_FLOAT, 20.5, 42),
                    array(Lexer::T_FLOAT, 25.9, 47),
                    array(Lexer::T_COMMA, ',', 51),
                    array(Lexer::T_FLOAT, 0.053, 53),
                    array(Lexer::T_INTEGER, 60, 61),
                    array(Lexer::T_CLOSE_PARENTHESIS, ')', 63)
                )
            )
        );
    }
}
