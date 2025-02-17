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

use CrEOF\Geo\WKT\Exception\ExceptionInterface;
use CrEOF\Geo\WKT\Exception\UnexpectedValueException;
use CrEOF\Geo\WKT\Parser;

/**
 * Basic parser tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $value
     * @param array  $expected
     *
     * @dataProvider parserTestData
     */
    public function testParser($value, $expected)
    {
        $parser = new Parser($value);

        if ($expected instanceof ExceptionInterface) {
            $this->setExpectedException(get_class($expected), $expected->getMessage());
        }

        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    public function testReusedParser()
    {
        $parser = new Parser();

        foreach ($this->parserTestData() as $name => $testData) {
            $value    = $testData['value'];
            $expected = $testData['expected'];

            if ($expected instanceof ExceptionInterface) {
                $this->setExpectedException(get_class($expected), $expected->getMessage());
            }

            $actual = $parser->parse($value);

            $this->assertEquals($expected, $actual, 'Failed dataset "'. $name . '"');
        }
    }

    /**
     * @return array[]
     */
    public function parserTestData()
    {
        return array(
            'testParsingGarbage' => array(
                'value'    => '@#_$%',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 0: Error: Expected CrEOF\Geo\WKT\Lexer::T_TYPE, got "@" in value "@#_$%"')
            ),
            'testParsingBadType' => array(
                'value'    => 'PNT(10 10)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 0: Error: Expected CrEOF\Geo\WKT\Lexer::T_TYPE, got "PNT" in value "PNT(10 10)"')
            ),
            'testParsingPointValue' => array(
                'value'    => 'POINT(34.23 -87)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87),
                    'dimension' => null
                )
            ),
            'testParsingPointZValue' => array(
                'value'    => 'POINT(34.23 -87 10)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87, 10),
                    'dimension' => 'Z'
                )
            ),
            'testParsingPointDeclaredZValue' => array(
                'value'    => 'POINTZ(34.23 -87 10)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87, 10),
                    'dimension' => 'Z'
                )
            ),
            'testParsingPointMValue' => array(
                'value'    => 'POINTM(34.23 -87 10)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87, 10),
                    'dimension' => 'M'
                )
            ),
            'testParsingPointZMValue' => array(
                'value'    => 'POINT(34.23 -87 10 30)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87, 10, 30),
                    'dimension' => 'ZM'
                )
            ),
            'testParsingPointDeclaredZMValue' => array(
                'value'    => 'POINT ZM(34.23 -87 10 30)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87, 10, 30),
                    'dimension' => 'ZM'
                )
            ),
            'testParsingPointValueWithSrid' => array(
                'value'    => 'SRID=4326;POINT(34.23 -87)',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87),
                    'dimension' => null
                )
            ),
            'testParsingPointZValueWithSrid' => array(
                'value'    => 'SRID=4326;POINT(34.23 -87 10)',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'POINT',
                    'value'     => array(34.23, -87, 10),
                    'dimension' => 'Z'
                )
            ),
            'testParsingPointValueScientificWithSrid' => array(
                'value'    => 'SRID=4326;POINT(4.23e-005 -8E-003)',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'POINT',
                    'value'     => array(0.0000423, -0.008),
                    'dimension' => null
                )
            ),
            'testParsingPointValueWithBadSrid' => array(
                'value'    => 'SRID=432.6;POINT(34.23 -87)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 5: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got "432.6" in value "SRID=432.6;POINT(34.23 -87)"')
            ),
            'testParsingPointValueMissingCoordinate' => array(
                'value'    => 'POINT(34.23)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 11: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got ")" in value "POINT(34.23)"')
            ),
            'testParsingPointMValueMissingCoordinate' => array(
                'value'    => 'POINTM(34.23 10)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 15: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got ")" in value "POINTM(34.23 10)"')
            ),
            'testParsingPointMValueExtraCoordinate' => array(
                'value'    => 'POINTM(34.23 10 30 40)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 19: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "40" in value "POINTM(34.23 10 30 40)"')
            ),
            'testParsingPointZMValueMissingCoordinate' => array(
                'value'    => 'POINTZM(34.23 10 45)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 19: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got ")" in value "POINTZM(34.23 10 45)"')
            ),
            'testParsingPointZMValueExtraCoordinate' => array(
                'value'    => 'POINTZM(34.23 10 45 4.5 99)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 24: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "99" in value "POINTZM(34.23 10 45 4.5 99)"')
            ),
            'testParsingPointValueShortString' => array(
                'value'    => 'POINT(34.23',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col -1: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got end of string. in value "POINT(34.23"')
            ),
            'testParsingPointValueWrongScientificWithSrid' => array(
                'value'    => 'SRID=4326;POINT(4.23test-005 -8e-003)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 20: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got "test" in value "SRID=4326;POINT(4.23test-005 -8e-003)"')
            ),
            'testParsingPointValueWithComma' => array(
                'value'    => 'POINT(10, 10)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 8: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got "," in value "POINT(10, 10)"')
            ),
            'testParsingLineStringValue' => array(
                'value'    => 'LINESTRING(34.23 -87, 45.3 -92)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'LINESTRING',
                    'value'     => array(
                        array(34.23, -87),
                        array(45.3, -92)
                    ),
                    'dimension' => null
                )
            ),
            'testParsingLineStringZValue' => array(
                'value'    => 'LINESTRING(34.23 -87 10, 45.3 -92 10)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'LINESTRING',
                    'value'     => array(
                        array(34.23, -87, 10),
                        array(45.3, -92, 10)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'testParsingLineStringMValue' => array(
                'value'    => 'LINESTRINGM(34.23 -87 10, 45.3 -92 10)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'LINESTRING',
                    'value'     => array(
                        array(34.23, -87, 10),
                        array(45.3, -92, 10)
                    ),
                    'dimension' => 'M'
                )
            ),
            'testParsingLineStringZMValue' => array(
                'value'    => 'LINESTRINGZM(34.23 -87 10 20, 45.3 -92 10 20)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'LINESTRING',
                    'value'     => array(
                        array(34.23, -87, 10, 20),
                        array(45.3, -92, 10, 20)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'testParsingLineStringValueWithSrid' => array(
                'value'    => 'SRID=4326;LINESTRING(34.23 -87, 45.3 -92)',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'LINESTRING',
                    'value'     => array(
                        array(34.23, -87),
                        array(45.3, -92)
                    ),
                    'dimension' => null
                )
            ),
            'testParsingLineStringValueMissingCoordinate' => array(
                'value'    => 'LINESTRING(34.23 -87, 45.3)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 26: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got ")" in value "LINESTRING(34.23 -87, 45.3)"')
            ),
            'testParsingLineStringValueMismatchedDimensions' => array(
                'value'    => 'LINESTRING(34.23 -87, 45.3 56 23.4)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 30: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "23.4" in value "LINESTRING(34.23 -87, 45.3 56 23.4)"')
            ),
            'testParsingPolygonValue' => array(
                'value'    => 'POLYGON((0 0,10 0,10 10,0 10,0 0))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0),
                            array(10, 0),
                            array(10, 10),
                            array(0, 10),
                            array(0, 0)
                        )
                    ) ,
                    'dimension' => null
                )
            ),
            'testParsingPolygonZValue' => array(
                'value'    => 'POLYGON((0 0 0,10 0 0,10 10 0,0 10 0,0 0 0))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0, 0),
                            array(10, 0, 0),
                            array(10, 10, 0),
                            array(0, 10, 0),
                            array(0, 0, 0)
                        )
                    ) ,
                    'dimension' => 'Z'
                )
            ),
            'testParsingPolygonMValue' => array(
                'value'    => 'POLYGONM((0 0 0,10 0 0,10 10 0,0 10 0,0 0 0))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0, 0),
                            array(10, 0, 0),
                            array(10, 10, 0),
                            array(0, 10, 0),
                            array(0, 0, 0)
                        )
                    ) ,
                    'dimension' => 'M'
                )
            ),
            'testParsingPolygonZMValue' => array(
                'value'    => 'POLYGONZM((0 0 0 1,10 0 0 1,10 10 0 1,0 10 0 1,0 0 0 1))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0, 0, 1),
                            array(10, 0, 0, 1),
                            array(10, 10, 0, 1),
                            array(0, 10, 0, 1),
                            array(0, 0, 0, 1)
                        )
                    ) ,
                    'dimension' => 'ZM'
                )
            ),
            'testParsingPolygonValueWithSrid' => array(
                'value'    => 'SRID=4326;POLYGON((0 0,10 0,10 10,0 10,0 0))',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0),
                            array(10, 0),
                            array(10, 10),
                            array(0, 10),
                            array(0, 0)
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingPolygonValueMultiRing' => array(
                'value'    => 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0),
                            array(10, 0),
                            array(10, 10),
                            array(0, 10),
                            array(0, 0)
                        ),
                        array(
                            array(5, 5),
                            array(7, 5),
                            array(7, 7),
                            array(5, 7),
                            array(5, 5)
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingPolygonValueMultiRingWithSrid' => array(
                'value'    => 'SRID=4326;POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(
                            array(0, 0),
                            array(10, 0),
                            array(10, 10),
                            array(0, 10),
                            array(0, 0)
                        ),
                        array(
                            array(5, 5),
                            array(7, 5),
                            array(7, 7),
                            array(5, 7),
                            array(5, 5)
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingPolygonValueMissingParenthesis' => array(
                'value'    => 'POLYGON(0 0,10 0,10 10,0 10,0 0)',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 8: Error: Expected CrEOF\Geo\WKT\Lexer::T_OPEN_PARENTHESIS, got "0" in value "POLYGON(0 0,10 0,10 10,0 10,0 0)"')
            ),
            'testParsingPolygonValueMismatchedDimension' => array(
                'value'    => 'POLYGON((0 0,10 0,10 10 10,0 10,0 0))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 24: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "10" in value "POLYGON((0 0,10 0,10 10 10,0 10,0 0))"')
            ),
            'testParsingPolygonValueMultiRingMissingComma' => array(
                'value'    => 'POLYGON((0 0,10 0,10 10,0 10,0 0)(5 5,7 5,7 7,5 7,5 5))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 33: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "(" in value "POLYGON((0 0,10 0,10 10,0 10,0 0)(5 5,7 5,7 7,5 7,5 5))"')
            ),
            'testParsingMultiPointValue' => array(
                'value'    => 'MULTIPOINT(0 0,10 0,10 10,0 10)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'MULTIPOINT',
                    'value'     => array(
                        array(0, 0),
                        array(10, 0),
                        array(10, 10),
                        array(0, 10)
                    ),
                    'dimension' => null
                )
            ),
            'testParsingMultiPointMValue' => array(
                'value'    => 'MULTIPOINTM(0 0 0,10 0 0,10 10 0,0 10 0)',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'MULTIPOINT',
                    'value'     => array(
                        array(0, 0, 0),
                        array(10, 0, 0),
                        array(10, 10, 0),
                        array(0, 10, 0)
                    ),
                    'dimension' => 'M'
                )
            ),
            'testParsingMultiPointValueWithSrid' => array(
                'value'    => 'SRID=4326;MULTIPOINT(0 0,10 0,10 10,0 10)',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'MULTIPOINT',
                    'value'     => array(
                        array(0, 0),
                        array(10, 0),
                        array(10, 10),
                        array(0, 10)
                    ),
                    'dimension' => null
                )
            ),
            'testParsingMultiPointValueWithExtraParenthesis' => array(
                'value'    => 'MULTIPOINT((0 0,10 0,10 10,0 10))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 11: Error: Expected CrEOF\Geo\WKT\Lexer::T_INTEGER, got "(" in value "MULTIPOINT((0 0,10 0,10 10,0 10))"')
            ),
            'testParsingMultiLineStringValue' => array(
                'value'    => 'MULTILINESTRING((0 0,10 0,10 10,0 10),(5 5,7 5,7 7,5 7))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'MULTILINESTRING',
                    'value'     => array(
                        array(
                            array(0, 0),
                            array(10, 0),
                            array(10, 10),
                            array(0, 10),
                        ),
                        array(
                            array(5, 5),
                            array(7, 5),
                            array(7, 7),
                            array(5, 7),
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingMultiLineStringZValue' => array(
                'value'    => 'MULTILINESTRING((0 0 0,10 0 0,10 10 0,0 10 0),(5 5 1,7 5 1,7 7 1,5 7 1))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'MULTILINESTRING',
                    'value'     => array(
                        array(
                            array(0, 0, 0),
                            array(10, 0, 0),
                            array(10, 10, 0),
                            array(0, 10, 0),
                        ),
                        array(
                            array(5, 5, 1),
                            array(7, 5, 1),
                            array(7, 7, 1),
                            array(5, 7, 1),
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'testParsingMultiLineStringValueWithSrid' => array(
                'value'    => 'SRID=4326;MULTILINESTRING((0 0,10 0,10 10,0 10),(5 5,7 5,7 7,5 7))',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'MULTILINESTRING',
                    'value'     => array(
                        array(
                            array(0, 0),
                            array(10, 0),
                            array(10, 10),
                            array(0, 10),
                        ),
                        array(
                            array(5, 5),
                            array(7, 5),
                            array(7, 7),
                            array(5, 7),
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingMultiLineStringValueMissingComma' => array(
                'value'    => 'MULTILINESTRING((0 0,10 0,10 10,0 10)(5 5,7 5,7 7,5 7))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 37: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "(" in value "MULTILINESTRING((0 0,10 0,10 10,0 10)(5 5,7 5,7 7,5 7))"')
            ),
            'testParsingMultiPolygonValue' => array(
                'value'    => 'MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5)),((1 1, 3 1, 3 3, 1 3, 1 1)))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'MULTIPOLYGON',
                    'value'     => array(
                        array(
                            array(
                                array(0, 0),
                                array(10, 0),
                                array(10, 10),
                                array(0, 10),
                                array(0, 0)
                            ),
                            array(
                                array(5, 5),
                                array(7, 5),
                                array(7, 7),
                                array(5, 7),
                                array(5, 5)
                            )
                        ),
                        array(
                            array(
                                array(1, 1),
                                array(3, 1),
                                array(3, 3),
                                array(1, 3),
                                array(1, 1)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingMultiPolygonValueWithSrid' => array(
                'value'    => 'SRID=4326;MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5)),((1 1, 3 1, 3 3, 1 3, 1 1)))',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'MULTIPOLYGON',
                    'value'     => array(
                        array(
                            array(
                                array(0, 0),
                                array(10, 0),
                                array(10, 10),
                                array(0, 10),
                                array(0, 0)
                            ),
                            array(
                                array(5, 5),
                                array(7, 5),
                                array(7, 7),
                                array(5, 7),
                                array(5, 5)
                            )
                        ),
                        array(
                            array(
                                array(1, 1),
                                array(3, 1),
                                array(3, 3),
                                array(1, 3),
                                array(1, 1)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingMultiPolygonValueMissingParenthesis' => array(
                'value'    => 'MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5)),(1 1, 3 1, 3 3, 1 3, 1 1))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 64: Error: Expected CrEOF\Geo\WKT\Lexer::T_OPEN_PARENTHESIS, got "1" in value "MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5)),(1 1, 3 1, 3 3, 1 3, 1 1))"')
            ),
            'testParsingGeometryCollectionValue' => array(
                'value'    => 'GEOMETRYCOLLECTION(POINT(10 10), POINT(30 30), LINESTRING(15 15, 20 20))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'GEOMETRYCOLLECTION',
                    'value'     => array(
                        array(
                            'type'      => 'POINT',
                            'value'     => array(10, 10)
                        ),
                        array(
                            'type'      => 'POINT',
                            'value'     => array(30, 30)
                        ),
                        array(
                            'type'      => 'LINESTRING',
                            'value'     => array(
                                array(15, 15),
                                array(20, 20)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingGeometryCollectionMValue' => array(
                'value'    => 'GEOMETRYCOLLECTIONM(POINT(10 10 0), POINT(30 30 0), LINESTRING(15 15 0, 20 20 0))',
                'expected' => array(
                    'srid'      => null,
                    'type'      => 'GEOMETRYCOLLECTION',
                    'value'     => array(
                        array(
                            'type'      => 'POINT',
                            'value'     => array(10, 10, 0)
                        ),
                        array(
                            'type'      => 'POINT',
                            'value'     => array(30, 30, 0)
                        ),
                        array(
                            'type'      => 'LINESTRING',
                            'value'     => array(
                                array(15, 15, 0),
                                array(20, 20, 0)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'testParsingGeometryCollectionValueWithSrid' => array(
                'value'    => 'SRID=4326;GEOMETRYCOLLECTION(POINT(10 10), POINT(30 30), LINESTRING(15 15, 20 20))',
                'expected' => array(
                    'srid'      => 4326,
                    'type'      => 'GEOMETRYCOLLECTION',
                    'value'     => array(
                        array(
                            'type'      => 'POINT',
                            'value'     => array(10, 10)
                        ),
                        array(
                            'type'      => 'POINT',
                            'value'     => array(30, 30)
                        ),
                        array(
                            'type'      => 'LINESTRING',
                            'value'     => array(
                                array(15, 15),
                                array(20, 20)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'testParsingGeometryCollectionValueWithBadType' => array(
                'value'    => 'GEOMETRYCOLLECTION(PNT(10 10), POINT(30 30), LINESTRING(15 15, 20 20))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 19: Error: Expected CrEOF\Geo\WKT\Lexer::T_TYPE, got "PNT" in value "GEOMETRYCOLLECTION(PNT(10 10), POINT(30 30), LINESTRING(15 15, 20 20))"')
            ),
            'testParsingGeometryCollectionValueWithMismatchedDimenstion' => array(
                'value'    => 'GEOMETRYCOLLECTION(POINT(10 10), POINT(30 30 10), LINESTRING(15 15, 20 20))',
                'expected' => new UnexpectedValueException('[Syntax Error] line 0, col 45: Error: Expected CrEOF\Geo\WKT\Lexer::T_CLOSE_PARENTHESIS, got "10" in value "GEOMETRYCOLLECTION(POINT(10 10), POINT(30 30 10), LINESTRING(15 15, 20 20))"')
            )
        );
    }
}
