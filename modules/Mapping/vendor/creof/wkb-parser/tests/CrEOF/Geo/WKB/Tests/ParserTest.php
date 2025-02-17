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

namespace CrEOF\Geo\WKB\Tests;

use CrEOF\Geo\WKB\Parser;

/**
 * Parser tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 *
 * @covers \CrEOF\Geo\WKB\Parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param mixed  $value
     * @param string $exception
     * @param string $message
     *
     * @dataProvider badBinaryData
     */
    public function testBadBinaryData($value, $exception, $message)
    {
        if (version_compare(\PHPUnit_Runner_Version::id(), '5.0', '>=')) {
            $this->expectException($exception);

            if ('/' === $message[0]) {
                $this->expectExceptionMessageRegExp($message);
            } else {
                $this->expectExceptionMessage($message);
            }
        } else {
            if ('/' === $message[0]) {
                $this->setExpectedExceptionRegExp($exception, $message);
            } else {
                $this->setExpectedException($exception, $message);
            }
        }

        $parser = new Parser($value);

        $parser->parse();
    }

    /**
     * @return array[]
     */
    public function badBinaryData()
    {
        return array(
            'badByteOrder' => array(
                'value'     => pack('H*', '03010000003D0AD7A3701D41400000000000C055C0'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Invalid byte order "3" at byte 0'
            ),
            'badSimpleType' => array(
                'value'     => pack('H*', '01150000003D0AD7A3701D41400000000000C055C0'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Unsupported WKB type "21" at byte 1'
            ),
            'shortNDRPoint' => array(
                'value'     => pack('H*', '01010000003D0AD7A3701D414000000000'),
                'exception' => 'CrEOF\Geo\WKB\Exception\RangeException',
                'message'   => '/Type d: not enough input, need 8, have 4 at byte 5$/'
            ),
            'badPointSize' => array(
                'value'     => pack('H*', '0000000FA1'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'POINT with unsupported dimensions 0xFA0 (4000) at byte 1'
            ),
            'badPointInMultiPoint' => array(
                'value'     => pack('H*', '0080000004000000020000000001'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Bad POINT with dimensions 0x0 (0) in MULTIPOINT, expected dimensions 0x80000000 (2147483648) at byte 10'
            ),
            'unexpectedLineStringInMultiPoint' => array(
                'value'     => pack('H*', '0080000004000000020000000002'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Unexpected LINESTRING with dimensions 0x0 (0) in MULTIPOINT, expected POINT with dimensions 0x80000000 (2147483648) at byte 10'
            ),
            'badLineStringInMultiLineString' => array(
                'value'     => pack('H*', '0000000005000000020080000002'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Bad LINESTRING with dimensions 0x80000000 (2147483648) in MULTILINESTRING, expected dimensions 0x0 (0) at byte 10'
            ),
            'badPolygonInMultiPolygon' => array(
                'value'     => pack('H*', '0080000006000000020000000003'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Bad POLYGON with dimensions 0x0 (0) in MULTIPOLYGON, expected dimensions 0x80000000 (2147483648) at byte 10'
            ),
            'badCircularStringInCompoundCurve' => array(
                'value'     => pack('H*', '0080000009000000020000000008'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Bad CIRCULARSTRING with dimensions 0x0 (0) in COMPOUNDCURVE, expected dimensions 0x80000000 (2147483648) at byte 10'
            ),
            'unexpectedPointInCompoundCurve' => array(
                'value'     => pack('H*', '0080000009000000020000000001'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Unexpected POINT with dimensions 0x0 (0) in COMPOUNDCURVE, expected LINESTRING or CIRCULARSTRING with dimensions 0x80000000 (2147483648) at byte 10'
            ),
            'badCompoundCurveInCurvePolygon' => array(
                'value'     => pack('H*', '000000000a000000010080000009'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Bad COMPOUNDCURVE with dimensions 0x80000000 (2147483648) in CURVEPOLYGON, expected dimensions 0x0 (0) at byte 10'
            ),
            'badCircularStringInCurvePolygon' => array(
                'value'     => pack('H*', '008000000a000000010080000009000000020000000008'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Bad CIRCULARSTRING with dimensions 0x0 (0) in CURVEPOLYGON, expected dimensions 0x80000000 (2147483648) at byte 19'
            ),
            'unexpectedPolygonInMultiCurve' => array(
                'value'     => pack('H*', '004000000b000000010040000003'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Unexpected POLYGON with dimensions 0x40000000 (1073741824) in MULTICURVE, expected LINESTRING, CIRCULARSTRING or COMPOUNDCURVE with dimensions 0x40000000 (1073741824) at byte 10'
            ),
            'unexpectedPointInMultiSurface' => array(
                'value'     => pack('H*', '008000000c000000020080000001'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Unexpected POINT with dimensions 0x80000000 (2147483648) in MULTISURFACE, expected POLYGON or CURVEPOLYGON with dimensions 0x80000000 (2147483648) at byte 10'
            ),
            'unexpectedPointInPolyhedralSurface' => array(
                'value'     => pack('H*', '010f000080050000000101000080'),
                'exception' => 'CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Unexpected POINT with dimensions 0x80000000 (2147483648) in POLYHEDRALSURFACE, expected POLYGON with dimensions 0x80000000 (2147483648) at byte 10'
            ),
        );
    }

    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider goodBinaryData
     */
    public function testParserRawHex($value, array $expected)
    {
        $parser = new Parser($value);
        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider goodBinaryData
     */
    public function testParserPrependLowerXHex($value, array $expected)
    {
        $parser = new Parser('x' . $value);
        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider goodBinaryData
     */
    public function testParserPrependUpperXHex($value, array $expected)
    {
        $parser = new Parser('X' . $value);
        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider goodBinaryData
     */
    public function testParserPrependLower0XHex($value, array $expected)
    {
        $parser = new Parser('0x' . $value);
        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider goodBinaryData
     */
    public function testParserPrependUpper0XHex($value, array $expected)
    {
        $parser = new Parser('0X' . $value);
        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param       $value
     * @param array $expected
     *
     * @dataProvider goodBinaryData
     */
    public function testParserBinary($value, array $expected)
    {
        $parser = new Parser(pack('H*', $value));
        $actual = $parser->parse();

        $this->assertEquals($expected, $actual);
    }

    public function testReusedParser()
    {
        $parser = new Parser();

        foreach ($this->goodBinaryData() as $testData) {
            $actual = $parser->parse($testData['value']);

            $this->assertEquals($testData['expected'], $actual);

            $actual = $parser->parse('x' . $testData['value']);

            $this->assertEquals($testData['expected'], $actual);

            $actual = $parser->parse('X' . $testData['value']);

            $this->assertEquals($testData['expected'], $actual);

            $actual = $parser->parse('0x' . $testData['value']);

            $this->assertEquals($testData['expected'], $actual);

            $actual = $parser->parse('0X' . $testData['value']);

            $this->assertEquals($testData['expected'], $actual);

            $actual = $parser->parse(pack('H*', $testData['value']));

            $this->assertEquals($testData['expected'], $actual);
        }
    }

    /**
     * @return array
     */
    public function goodBinaryData()
    {
        return array(
            'ndrEmptyPointValue' => array(
                'value' => '0101000000000000000000F87F000000000000F87F',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(),
                    'dimension' => null
                )
            ),
            'ndrPointValue' => array(
                'value' => '01010000003D0AD7A3701D41400000000000C055C0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(34.23, -87),
                    'dimension' => null
                )
            ),
            'xdrPointValue' => array(
                'value' => '000000000140411D70A3D70A3DC055C00000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(34.23, -87),
                    'dimension' => null
                )
            ),
            'ndrPointZValue' => array(
                'value' => '0101000080000000000000F03F00000000000000400000000000000840',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'Z'
                )
            ),
            'xdrPointZValue' => array(
                'value' => '00800000013FF000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'Z'
                )
            ),
            'xdrPointZOGCValue' => array(
                'value' => '00000003E94117C89F84189375411014361BA5E3540000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(389671.879, 263437.527, 0),
                    'dimension' => 'Z'
                )
            ),
            'ndrPointMValue' => array(
                'value' => '0101000040000000000000F03F00000000000000400000000000000840',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'M'
                )
            ),
            'xdrPointMValue' => array(
                'value' => '00400000013FF000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'M'
                )
            ),
            'ndrEmptyPointZMValue' => array(
                'value' => '01010000C0000000000000F87F000000000000F87F000000000000F87F000000000000F87F',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(),
                    'dimension' => 'ZM'
                )
            ),
            'xdrEmptyPointZMValue' => array(
                'value' => '00C00000017FF80000000000007FF80000000000007FF80000000000007FF8000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(),
                    'dimension' => 'ZM'
                )
            ),
            'ndrPointZMValue' => array(
                'value' => '01010000C0000000000000F03F000000000000004000000000000008400000000000001040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3, 4),
                    'dimension' => 'ZM'
                )
            ),
            'xdrPointZMValue' => array(
                'value' => '00C00000013FF0000000000000400000000000000040080000000000004010000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3, 4),
                    'dimension' => 'ZM'
                )
            ),
            'ndrPointValueWithSrid' => array(
                'value' => '01010000003D0AD7A3701D41400000000000C055C0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POINT',
                    'value' => array(34.23, -87),
                    'dimension' => null
                )
            ),
            'xdrPointValueWithSrid' => array(
                'value' => '0020000001000010E640411D70A3D70A3DC055C00000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(34.23, -87),
                    'dimension' => null
                )
            ),
            'ndrPointZValueWithSrid' => array(
                'value' => '01010000A0E6100000000000000000F03F00000000000000400000000000000840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'Z'
                )
            ),
            'xdrPointZValueWithSrid' => array(
                'value' => '00A0000001000010E63FF000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'Z'
                )
            ),
            'ndrPointMValueWithSrid' => array(
                'value' => '0101000060e6100000000000000000f03f00000000000000400000000000000840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'M'
                )
            ),
            'xdrPointMValueWithSrid' => array(
                'value' => '0060000001000010e63ff000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3),
                    'dimension' => 'M'
                )
            ),
            'ndrEmptyPointZMValueWithSrid' => array(
                'value' => '01010000E08C100000000000000000F87F000000000000F87F000000000000F87F000000000000F87F',
                'expected' => array(
                    'srid'  => 4236,
                    'type'  => 'POINT',
                    'value' => array(),
                    'dimension' => 'ZM'
                )
            ),
            'ndrPointZMValueWithSrid' => array(
                'value' => '01010000e0e6100000000000000000f03f000000000000004000000000000008400000000000001040',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3, 4),
                    'dimension' => 'ZM'
                )
            ),
            'xdrPointZMValueWithSrid' => array(
                'value' => '00e0000001000010e63ff0000000000000400000000000000040080000000000004010000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POINT',
                    'value' => array(1, 2, 3, 4),
                    'dimension' => 'ZM'
                )
            ),
            'ndrEmptyLineStringValue' => array(
                'value' => '010200000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(),
                    'dimension' => null
                )
            ),
            'ndrLineStringValue' => array(
                'value' => '0102000000020000003D0AD7A3701D41400000000000C055C06666666666A6464000000000000057C0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(34.23, -87),
                        array(45.3, -92)
                    ),
                    'dimension' => null
                )
            ),
            'xdrLineStringValue' => array(
                'value' => '00000000020000000240411D70A3D70A3DC055C000000000004046A66666666666C057000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(34.23, -87),
                        array(45.3, -92)
                    ),
                    'dimension' => null
                )
            ),
            'ndrLineStringZValue' => array(
                'value' => '010200008002000000000000000000000000000000000000000000000000000040000000000000f03f000000000'
                    . '000f03f0000000000000840',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrLineStringZValue' => array(
                'value' => '0080000002000000020000000000000000000000000000000040000000000000003ff00000000000003ff000000'
                    . '00000004008000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrLineStringMValue' => array(
                'value' => '010200004002000000000000000000000000000000000000000000000000000040000000000000f03f000000000'
                    . '000f03f0000000000000840',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrLineStringMValue' => array(
                'value' => '0040000002000000020000000000000000000000000000000040000000000000003ff00000000000003ff000000'
                    . '00000004008000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrLineStringZMValue' => array(
                'value' => '01020000c0020000000000000000000000000000000000000000000000000000400000000000000840000000000'
                    . '000f03f000000000000f03f00000000000010400000000000001440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2, 3),
                        array(1, 1, 4, 5)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrLineStringZMValue' => array(
                'value' => '00c00000020000000200000000000000000000000000000000400000000000000040080000000000003ff000000'
                    . '00000003ff000000000000040100000000000004014000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2, 3),
                        array(1, 1, 4, 5)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrLineStringValueWithSrid' => array(
                'value' => '0102000020E6100000020000003D0AD7A3701D41400000000000C055C06666666666A6464000000000000057C0',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(34.23, -87),
                        array(45.3, -92)
                    ),
                    'dimension' => null
                )
            ),
            'xdrLineStringValueWithSrid' => array(
                'value' => '0020000002000010E60000000240411D70A3D70A3DC055C000000000004046A66666666666C057000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(34.23, -87),
                        array(45.3, -92)
                    ),
                    'dimension' => null
                )
            ),
            'ndrLineStringZValueWithSrid' => array(
                'value' => '01020000a0e610000002000000000000000000000000000000000000000000000000000040000000000000f03f0'
                    . '00000000000f03f0000000000000840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrLineStringZValueWithSrid' => array(
                'value' => '00a0000002000010e6000000020000000000000000000000000000000040000000000000003ff00000000000003'
                    . 'ff00000000000004008000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrLineStringMValueWithSrid' => array(
                'value' => '0102000060e610000002000000000000000000000000000000000000000000000000000040000000000000f03f0'
                    . '00000000000f03f0000000000000840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrLineStringMValueWithSrid' => array(
                'value' => '0060000002000010e6000000020000000000000000000000000000000040000000000000003ff00000000000003'
                    . 'ff00000000000004008000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2),
                        array(1, 1, 3)
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrLineStringZMValueWithSrid' => array(
                'value' => '01020000e0e61000000200000000000000000000000000000000000000000000000000004000000000000008400'
                    . '00000000000f03f000000000000f03f00000000000010400000000000001440',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2, 3),
                        array(1, 1, 4, 5)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrLineStringZMValueWithSrid' => array(
                'value' => '00e0000002000010e60000000200000000000000000000000000000000400000000000000040080000000000003'
                    . 'ff00000000000003ff000000000000040100000000000004014000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'LINESTRING',
                    'value' => array(
                        array(0, 0, 2, 3),
                        array(1, 1, 4, 5)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrEmptyPolygonValue' => array(
                'value' => '010300000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(),
                    'dimension' => null
                )
            ),
            'ndrPolygonValue' => array(
                'value' => '0103000000010000000500000000000000000000000000000000000000000000000000244000000000000000000'
                    . '00000000000244000000000000024400000000000000000000000000000244000000000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'xdrPolygonValue' => array(
                'value' => '0000000003000000010000000500000000000000000000000000000000402400000000000000000000000000004'
                    . '02400000000000040240000000000000000000000000000402400000000000000000000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'ndrPolygonValueWithSrid' => array(
                'value' => '0103000020E61000000100000005000000000000000000000000000000000000000000000000002440000000000'
                    . '000000000000000000024400000000000002440000000000000000000000000000024400000000000000000000000000'
                    . '0000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'xdrPolygonValueWithSrid' => array(
                'value' => '0020000003000010E60000000100000005000000000000000000000000000000004024000000000000000000000'
                    . '000000040240000000000004024000000000000000000000000000040240000000000000000000000000000000000000'
                    . '0000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'ndrMultiRingPolygonValue' => array(
                'value' => '0103000000020000000500000000000000000000000000000000000000000000000000244000000000000000000'
                    . '000000000002440000000000000244000000000000000000000000000002440000000000000000000000000000000000'
                    . '5000000000000000000144000000000000014400000000000001C4000000000000014400000000000001C40000000000'
                    . '0001C4000000000000014400000000000001C4000000000000014400000000000001440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'xdrMultiRingPolygonValue' => array(
                'value' => '0000000003000000020000000500000000000000000000000000000000402400000000000000000000000000004'
                    . '024000000000000402400000000000000000000000000004024000000000000000000000000000000000000000000000'
                    . '000000540140000000000004014000000000000401C0000000000004014000000000000401C000000000000401C00000'
                    . '00000004014000000000000401C00000000000040140000000000004014000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'ndrMultiRingPolygonZValue' => array(
                'value' => '0103000080020000000500000000000000000000000000000000000000000000000000f03f00000000000024400'
                    . '000000000000000000000000000004000000000000024400000000000002440000000000000004000000000000000000'
                    . '000000000002440000000000000004000000000000000000000000000000000000000000000f03f05000000000000000'
                    . '000004000000000000000400000000000001440000000000000004000000000000014400000000000001040000000000'
                    . '000144000000000000014400000000000000840000000000000144000000000000000400000000000000840000000000'
                    . '000004000000000000000400000000000001440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiRingPolygonZValue' => array(
                'value' => '00800000030000000200000005000000000000000000000000000000003ff000000000000040240000000000000'
                    . '000000000000000400000000000000040240000000000004024000000000000400000000000000000000000000000004'
                    . '0240000000000004000000000000000000000000000000000000000000000003ff000000000000000000005400000000'
                    . '000000040000000000000004014000000000000400000000000000040140000000000004010000000000000401400000'
                    . '000000040140000000000004008000000000000401400000000000040000000000000004008000000000000400000000'
                    . '000000040000000000000004014000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiRingPolygonMValue' => array(
                'value' => '0103000040020000000500000000000000000000000000000000000000000000000000f03f00000000000024400'
                    . '000000000000000000000000000004000000000000024400000000000002440000000000000004000000000000000000'
                    . '000000000002440000000000000004000000000000000000000000000000000000000000000f03f05000000000000000'
                    . '000004000000000000000400000000000001440000000000000004000000000000014400000000000001040000000000'
                    . '000144000000000000014400000000000000840000000000000144000000000000000400000000000000840000000000'
                    . '000004000000000000000400000000000001440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiRingPolygonMValue' => array(
                'value' => '00400000030000000200000005000000000000000000000000000000003ff000000000000040240000000000000'
                    . '000000000000000400000000000000040240000000000004024000000000000400000000000000000000000000000004'
                    . '0240000000000004000000000000000000000000000000000000000000000003ff000000000000000000005400000000'
                    . '000000040000000000000004014000000000000400000000000000040140000000000004010000000000000401400000'
                    . '000000040140000000000004008000000000000401400000000000040000000000000004008000000000000400000000'
                    . '000000040000000000000004014000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiRingPolygonZMValue' => array(
                'value' => '01030000c0020000000500000000000000000000000000000000000000000000000000f03f000000000000f0bf0'
                    . '0000000000024400000000000000000000000000000004000000000000000c0000000000000244000000000000024400'
                    . '00000000000004000000000000000c000000000000000000000000000002440000000000000004000000000000010c00'
                    . '0000000000000000000000000000000000000000000f03f000000000000f0bf050000000000000000000040000000000'
                    . '000004000000000000014400000000000000000000000000000004000000000000014400000000000001040000000000'
                    . '000f03f00000000000014400000000000001440000000000000084000000000000000400000000000001440000000000'
                    . '00000400000000000000840000000000000f03f000000000000004000000000000000400000000000001440000000000'
                    . '0000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1, -1),
                            array(10, 0, 2, -2),
                            array(10, 10, 2, -2),
                            array(0, 10, 2, -4),
                            array(0, 0, 1, -1)
                        ),
                        array(
                            array(2, 2, 5, 0),
                            array(2, 5, 4, 1),
                            array(5, 5, 3, 2),
                            array(5, 2, 3, 1),
                            array(2, 2, 5, 0)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiRingPolygonZMValue' => array(
                'value' => '00c00000030000000200000005000000000000000000000000000000003ff0000000000000bff00000000000004'
                    . '02400000000000000000000000000004000000000000000c000000000000000402400000000000040240000000000004'
                    . '000000000000000c000000000000000000000000000000040240000000000004000000000000000c0100000000000000'
                    . '00000000000000000000000000000003ff0000000000000bff0000000000000000000054000000000000000400000000'
                    . '0000000401400000000000000000000000000004000000000000000401400000000000040100000000000003ff000000'
                    . '000000040140000000000004014000000000000400800000000000040000000000000004014000000000000400000000'
                    . '000000040080000000000003ff0000000000000400000000000000040000000000000004014000000000000000000000'
                    . '0000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1, -1),
                            array(10, 0, 2, -2),
                            array(10, 10, 2, -2),
                            array(0, 10, 2, -4),
                            array(0, 0, 1, -1)
                        ),
                        array(
                            array(2, 2, 5, 0),
                            array(2, 5, 4, 1),
                            array(5, 5, 3, 2),
                            array(5, 2, 3, 1),
                            array(2, 2, 5, 0)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiRingPolygonValueWithSrid' => array(
                'value' => '0103000020E61000000200000005000000000000000000000000000000000000000000000000002440000000000'
                    . '000000000000000000024400000000000002440000000000000000000000000000024400000000000000000000000000'
                    . '000000005000000000000000000144000000000000014400000000000001C4000000000000014400000000000001C400'
                    . '000000000001C4000000000000014400000000000001C4000000000000014400000000000001440',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'xdrMultiRingPolygonValueWithSrid' => array(
                'value' => '0020000003000010E60000000200000005000000000000000000000000000000004024000000000000000000000'
                    . '000000040240000000000004024000000000000000000000000000040240000000000000000000000000000000000000'
                    . '00000000000000540140000000000004014000000000000401C0000000000004014000000000000401C0000000000004'
                    . '01C0000000000004014000000000000401C00000000000040140000000000004014000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
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
            'ndrMultiRingPolygonZValueWithSrid' => array(
                'value' => '01030000a0e6100000020000000500000000000000000000000000000000000000000000000000f03f000000000'
                    . '000244000000000000000000000000000000040000000000000244000000000000024400000000000000040000000000'
                    . '00000000000000000002440000000000000004000000000000000000000000000000000000000000000f03f050000000'
                    . '000000000000040000000000000004000000000000014400000000000000040000000000000144000000000000010400'
                    . '000000000001440000000000000144000000000000008400000000000001440000000000000004000000000000008400'
                    . '00000000000004000000000000000400000000000001440',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiRingPolygonZValueWithSrid' => array(
                'value' => '00a0000003000010e60000000200000005000000000000000000000000000000003ff0000000000000402400000'
                    . '000000000000000000000004000000000000000402400000000000040240000000000004000000000000000000000000'
                    . '000000040240000000000004000000000000000000000000000000000000000000000003ff0000000000000000000054'
                    . '000000000000000400000000000000040140000000000004000000000000000401400000000000040100000000000004'
                    . '014000000000000401400000000000040080000000000004014000000000000400000000000000040080000000000004'
                    . '00000000000000040000000000000004014000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiRingPolygonMValueWithSrid' => array(
                'value' => '0103000060e6100000020000000500000000000000000000000000000000000000000000000000f03f000000000'
                    . '000244000000000000000000000000000000040000000000000244000000000000024400000000000000040000000000'
                    . '00000000000000000002440000000000000004000000000000000000000000000000000000000000000f03f050000000'
                    . '000000000000040000000000000004000000000000014400000000000000040000000000000144000000000000010400'
                    . '000000000001440000000000000144000000000000008400000000000001440000000000000004000000000000008400'
                    . '00000000000004000000000000000400000000000001440',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiRingPolygonMValueWithSrid' => array(
                'value' => '0060000003000010e60000000200000005000000000000000000000000000000003ff0000000000000402400000'
                    . '000000000000000000000004000000000000000402400000000000040240000000000004000000000000000000000000'
                    . '000000040240000000000004000000000000000000000000000000000000000000000003ff0000000000000000000054'
                    . '000000000000000400000000000000040140000000000004000000000000000401400000000000040100000000000004'
                    . '014000000000000401400000000000040080000000000004014000000000000400000000000000040080000000000004'
                    . '00000000000000040000000000000004014000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(10, 0, 2),
                            array(10, 10, 2),
                            array(0, 10, 2),
                            array(0, 0, 1)
                        ),
                        array(
                            array(2, 2, 5),
                            array(2, 5, 4),
                            array(5, 5, 3),
                            array(5, 2, 3),
                            array(2, 2, 5)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiRingPolygonZMValueWithSrid' => array(
                'value' => '01030000e0e6100000020000000500000000000000000000000000000000000000000000000000f03f000000000'
                    . '000f0bf00000000000024400000000000000000000000000000004000000000000000c00000000000002440000000000'
                    . '0002440000000000000004000000000000000c0000000000000000000000000000024400000000000000040000000000'
                    . '00010c000000000000000000000000000000000000000000000f03f000000000000f0bf0500000000000000000000400'
                    . '000000000000040000000000000144000000000000000000000000000000040000000000000144000000000000010400'
                    . '00000000000f03f000000000000144000000000000014400000000000000840000000000000004000000000000014400'
                    . '0000000000000400000000000000840000000000000f03f0000000000000040000000000000004000000000000014400'
                    . '000000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1, -1),
                            array(10, 0, 2, -2),
                            array(10, 10, 2, -2),
                            array(0, 10, 2, -4),
                            array(0, 0, 1, -1)
                        ),
                        array(
                            array(2, 2, 5, 0),
                            array(2, 5, 4, 1),
                            array(5, 5, 3, 2),
                            array(5, 2, 3, 1),
                            array(2, 2, 5, 0)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiRingPolygonZMValueWithSrid' => array(
                'value' => '00e0000003000010e60000000200000005000000000000000000000000000000003ff0000000000000bff000000'
                    . '0000000402400000000000000000000000000004000000000000000c0000000000000004024000000000000402400000'
                    . '00000004000000000000000c000000000000000000000000000000040240000000000004000000000000000c01000000'
                    . '0000000000000000000000000000000000000003ff0000000000000bff00000000000000000000540000000000000004'
                    . '000000000000000401400000000000000000000000000004000000000000000401400000000000040100000000000003'
                    . 'ff0000000000000401400000000000040140000000000004008000000000000400000000000000040140000000000004'
                    . '00000000000000040080000000000003ff00000000000004000000000000000400000000000000040140000000000000'
                    . '000000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'POLYGON',
                    'value' => array(
                        array(
                            array(0, 0, 1, -1),
                            array(10, 0, 2, -2),
                            array(10, 10, 2, -2),
                            array(0, 10, 2, -4),
                            array(0, 0, 1, -1)
                        ),
                        array(
                            array(2, 2, 5, 0),
                            array(2, 5, 4, 1),
                            array(5, 5, 3, 2),
                            array(5, 2, 3, 1),
                            array(2, 2, 5, 0)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiPointValue' => array(
                'value' => '0104000000040000000101000000000000000000000000000000000000000101000000000000000000244000000'
                    . '00000000000010100000000000000000024400000000000002440010100000000000000000000000000000000002440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0),
                        array(10, 0),
                        array(10, 10),
                        array(0, 10)
                    ),
                    'dimension' => null
                )
            ),
            'xdrMultiPointValue' => array(
                'value' => '0000000004000000040000000001000000000000000000000000000000000000000001402400000000000000000'
                    . '00000000000000000000140240000000000004024000000000000000000000100000000000000004024000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0),
                        array(10, 0),
                        array(10, 10),
                        array(0, 10)
                    ),
                    'dimension' => null
                )
            ),
            'ndrMultiPointZValue' => array(
                'value' => '0104000080020000000101000080000000000000000000000000000000000000000000000000010100008000000'
                    . '000000000400000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 0),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiPointZValue' => array(
                'value' => '0080000004000000020080000001000000000000000000000000000000000000000000000000008000000140000'
                    . '0000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 0),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiPointMValue' => array(
                'value' => '0104000040020000000101000040000000000000000000000000000000000000000000000040010100004000000'
                    . '000000000400000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 2),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiPointMValue' => array(
                'value' => '0040000004000000020040000001000000000000000000000000000000004000000000000000004000000140000'
                    . '0000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 2),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiPointZMValue' => array(
                'value' => '01040000c00200000001010000c00000000000000000000000000000f03f0000000000000040000000000000084'
                    . '001010000c000000000000008400000000000000040000000000000f03f0000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 1, 2, 3),
                        array(3, 2, 1, 0)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiPointZMValue' => array(
                'value' => '00c00000040000000200c000000100000000000000003ff00000000000004000000000000000400800000000000'
                    . '000c0000001400800000000000040000000000000003ff00000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 1, 2, 3),
                        array(3, 2, 1, 0)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiPointValueWithSrid' => array(
                'value' => '0104000020E61000000400000001010000000000000000000000000000000000000001010000000000000000002'
                    . '440000000000000000001010000000000000000002440000000000000244001010000000000000000000000000000000'
                    . '0002440',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0),
                        array(10, 0),
                        array(10, 10),
                        array(0, 10)
                    ),
                    'dimension' => null
                )
            ),
            'xdrMultiPointValueWithSrid' => array(
                'value' => '0020000004000010E60000000400000000010000000000000000000000000000000000000000014024000000000'
                    . '000000000000000000000000000014024000000000000402400000000000000000000010000000000000000402400000'
                    . '0000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0),
                        array(10, 0),
                        array(10, 10),
                        array(0, 10)
                    ),
                    'dimension' => null
                )
            ),
            'ndrMultiPointZValueWithSrid' => array(
                'value' => '0104000080020000000101000080000000000000000000000000000000000000000000000000010100008000000'
                    . '000000000400000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 0),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiPointZValueWithSrid' => array(
                'value' => '0080000004000000020080000001000000000000000000000000000000000000000000000000008000000140000'
                    . '0000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 0),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiPointMValueWithSrid' => array(
                'value' => '0104000040020000000101000040000000000000000000000000000000000000000000000040010100004000000'
                    . '000000000400000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 2),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiPointMValueWithSrid' => array(
                'value' => '0040000004000000020040000001000000000000000000000000000000004000000000000000004000000140000'
                    . '0000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 0, 2),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiPointZMValueWithSrid' => array(
                'value' => '01040000c00200000001010000c00000000000000000000000000000f03f0000000000000040000000000000084'
                    . '001010000c000000000000008400000000000000040000000000000f03f0000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 1, 2, 3),
                        array(3, 2, 1, 0)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiPointZMValueWithSrid' => array(
                'value' => '00c00000040000000200c000000100000000000000003ff00000000000004000000000000000400800000000000'
                    . '000c0000001400800000000000040000000000000003ff00000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOINT',
                    'value' => array(
                        array(0, 1, 2, 3),
                        array(3, 2, 1, 0)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiLineStringValue' => array(
                'value' => '0105000000020000000102000000040000000000000000000000000000000000000000000000000024400000000'
                    . '000000000000000000000244000000000000024400000000000000000000000000000244001020000000400000000000'
                    . '0000000144000000000000014400000000000001C4000000000000014400000000000001C400000000000001C4000000'
                    . '000000014400000000000001C40',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
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
            'xdrMultiLineStringValue' => array(
                'value' => '0000000005000000020000000002000000040000000000000000000000000000000040240000000000000000000'
                    . '000000000402400000000000040240000000000000000000000000000402400000000000000000000020000000440140'
                    . '000000000004014000000000000401C0000000000004014000000000000401C000000000000401C00000000000040140'
                    . '00000000000401C000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
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
            'ndrMultiLineStringZValue' => array(
                'value' => '01050000800200000001020000800200000000000000000000000000000000000000000000000000f03f0000000'
                    . '00000004000000000000000000000000000000040010200008002000000000000000000f03f000000000000f03f00000'
                    . '00000000840000000000000004000000000000000400000000000001040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiLineStringZValue' => array(
                'value' => '008000000500000002008000000200000002000000000000000000000000000000003ff00000000000004000000'
                    . '000000000000000000000000040000000000000000080000002000000023ff00000000000003ff000000000000040080'
                    . '00000000000400000000000000040000000000000004010000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiLineStringMValue' => array(
                'value' => '01050000400200000001020000400200000000000000000000000000000000000000000000000000f03f0000000'
                    . '00000004000000000000000000000000000000040010200004002000000000000000000f03f000000000000f03f00000'
                    . '00000000840000000000000004000000000000000400000000000001040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiLineStringMValue' => array(
                'value' => '004000000500000002004000000200000002000000000000000000000000000000003ff00000000000004000000'
                    . '000000000000000000000000040000000000000000040000002000000023ff00000000000003ff000000000000040080'
                    . '00000000000400000000000000040000000000000004010000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiLineStringZMValue' => array(
                'value' => '01050000c00200000001020000c00200000000000000000000000000000000000000000000000000f03f0000000'
                    . '000001440000000000000004000000000000000000000000000000040000000000000104001020000c00200000000000'
                    . '0000000f03f000000000000f03f000000000000084000000000000008400000000000000040000000000000004000000'
                    . '000000010400000000000000040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1, 5),
                            array(2, 0, 2, 4)
                        ),
                        array(
                            array(1, 1, 3, 3),
                            array(2, 2, 4, 2)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiLineStringZMValue' => array(
                'value' => '00c00000050000000200c000000200000002000000000000000000000000000000003ff00000000000004014000'
                    . '000000000400000000000000000000000000000004000000000000000401000000000000000c0000002000000023ff00'
                    . '000000000003ff0000000000000400800000000000040080000000000004000000000000000400000000000000040100'
                    . '000000000004000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1, 5),
                            array(2, 0, 2, 4)
                        ),
                        array(
                            array(1, 1, 3, 3),
                            array(2, 2, 4, 2)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiLineStringValueWithSrid' => array(
                'value' => '0105000020E61000000200000001020000000400000000000000000000000000000000000000000000000000244'
                    . '000000000000000000000000000002440000000000000244000000000000000000000000000002440010200000004000'
                    . '000000000000000144000000000000014400000000000001C4000000000000014400000000000001C400000000000001'
                    . 'C4000000000000014400000000000001C40',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
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
            'xdrMultiLineStringValueWithSrid' => array(
                'value' => '0020000005000010E60000000200000000020000000400000000000000000000000000000000402400000000000'
                    . '000000000000000004024000000000000402400000000000000000000000000004024000000000000000000000200000'
                    . '00440140000000000004014000000000000401C0000000000004014000000000000401C000000000000401C000000000'
                    . '0004014000000000000401C000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
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
            'ndrMultiLineStringZValueWithSrid' => array(
                'value' => '01050000a0e61000000200000001020000800200000000000000000000000000000000000000000000000000f03'
                    . 'f000000000000004000000000000000000000000000000040010200008002000000000000000000f03f000000000000f'
                    . '03f0000000000000840000000000000004000000000000000400000000000001040',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiLineStringZValueWithSrid' => array(
                'value' => '008000000500000002008000000200000002000000000000000000000000000000003ff00000000000004000000'
                    . '000000000000000000000000040000000000000000080000002000000023ff00000000000003ff000000000000040080'
                    . '00000000000400000000000000040000000000000004010000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiLineStringMValueWithSrid' => array(
                'value' => '0105000060e61000000200000001020000400200000000000000000000000000000000000000000000000000f03'
                    . 'f000000000000004000000000000000000000000000000040010200004002000000000000000000f03f000000000000f'
                    . '03f0000000000000840000000000000004000000000000000400000000000001040',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiLineStringMValueWithSrid' => array(
                'value' => '004000000500000002004000000200000002000000000000000000000000000000003ff00000000000004000000'
                    . '000000000000000000000000040000000000000000040000002000000023ff00000000000003ff000000000000040080'
                    . '00000000000400000000000000040000000000000004010000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1),
                            array(2, 0, 2)
                        ),
                        array(
                            array(1, 1, 3),
                            array(2, 2, 4)
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiLineStringZMValueWithSrid' => array(
                'value' => '01050000e0e61000000200000001020000c00200000000000000000000000000000000000000000000000000f03'
                    . 'f0000000000001440000000000000004000000000000000000000000000000040000000000000104001020000c002000'
                    . '000000000000000f03f000000000000f03f0000000000000840000000000000084000000000000000400000000000000'
                    . '04000000000000010400000000000000040',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1, 5),
                            array(2, 0, 2, 4)
                        ),
                        array(
                            array(1, 1, 3, 3),
                            array(2, 2, 4, 2)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiLineStringZMValueWithSrid' => array(
                'value' => '00c00000050000000200c000000200000002000000000000000000000000000000003ff00000000000004014000'
                    . '000000000400000000000000000000000000000004000000000000000401000000000000000c0000002000000023ff00'
                    . '000000000003ff0000000000000400800000000000040080000000000004000000000000000400000000000000040100'
                    . '000000000004000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTILINESTRING',
                    'value' => array(
                        array(
                            array(0, 0, 1, 5),
                            array(2, 0, 2, 4)
                        ),
                        array(
                            array(1, 1, 3, 3),
                            array(2, 2, 4, 2)
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiPolygonValue' => array(
                'value' => '0106000000020000000103000000020000000500000000000000000000000000000000000000000000000000244'
                    . '000000000000000000000000000002440000000000000244000000000000000000000000000002440000000000000000'
                    . '0000000000000000005000000000000000000144000000000000014400000000000001C4000000000000014400000000'
                    . '000001C400000000000001C4000000000000014400000000000001C40000000000000144000000000000014400103000'
                    . '0000100000005000000000000000000F03F000000000000F03F0000000000000840000000000000F03F0000000000000'
                    . '8400000000000000840000000000000F03F0000000000000840000000000000F03F000000000000F03F',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
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
            'xdrMultiPolygonValue' => array(
                'value' => '0000000006000000020000000003000000020000000500000000000000000000000000000000402400000000000'
                    . '000000000000000004024000000000000402400000000000000000000000000004024000000000000000000000000000'
                    . '000000000000000000000000540140000000000004014000000000000401C0000000000004014000000000000401C000'
                    . '000000000401C0000000000004014000000000000401C000000000000401400000000000040140000000000000000000'
                    . '00300000001000000053FF00000000000003FF000000000000040080000000000003FF00000000000004008000000000'
                    . '00040080000000000003FF000000000000040080000000000003FF00000000000003FF0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
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
            'ndrMultiPolygonZValue' => array(
                'value' => '0106000080010000000103000080020000000500000000000000000000000000000000000000000000000000084'
                    . '000000000000024400000000000000000000000000000084000000000000024400000000000002440000000000000084'
                    . '000000000000000000000000000002440000000000000084000000000000000000000000000000000000000000000084'
                    . '005000000000000000000004000000000000000400000000000000840000000000000004000000000000014400000000'
                    . '000000840000000000000144000000000000014400000000000000840000000000000144000000000000000400000000'
                    . '000000840000000000000004000000000000000400000000000000840',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiPolygonZValue' => array(
                'value' => '0080000006000000010080000003000000020000000500000000000000000000000000000000400800000000000'
                    . '040240000000000000000000000000000400800000000000040240000000000004024000000000000400800000000000'
                    . '000000000000000004024000000000000400800000000000000000000000000000000000000000000400800000000000'
                    . '000000005400000000000000040000000000000004008000000000000400000000000000040140000000000004008000'
                    . '000000000401400000000000040140000000000004008000000000000401400000000000040000000000000004008000'
                    . '000000000400000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiPolygonMValue' => array(
                'value' => '0106000040010000000103000040020000000500000000000000000000000000000000000000000000000000084'
                    . '000000000000024400000000000000000000000000000084000000000000024400000000000002440000000000000084'
                    . '000000000000000000000000000002440000000000000084000000000000000000000000000000000000000000000084'
                    . '005000000000000000000004000000000000000400000000000000840000000000000004000000000000014400000000'
                    . '000000840000000000000144000000000000014400000000000000840000000000000144000000000000000400000000'
                    . '000000840000000000000004000000000000000400000000000000840',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiPolygonMValue' => array(
                'value' => '0040000006000000010040000003000000020000000500000000000000000000000000000000400800000000000'
                    . '040240000000000000000000000000000400800000000000040240000000000004024000000000000400800000000000'
                    . '000000000000000004024000000000000400800000000000000000000000000000000000000000000400800000000000'
                    . '000000005400000000000000040000000000000004008000000000000400000000000000040140000000000004008000'
                    . '000000000401400000000000040140000000000004008000000000000401400000000000040000000000000004008000'
                    . '000000000400000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiPolygonZMValue' => array(
                'value' => '01060000c00100000001030000c0020000000500000000000000000000000000000000000000000000000000084'
                    . '000000000000000400000000000002440000000000000000000000000000008400000000000000040000000000000244'
                    . '000000000000024400000000000000840000000000000004000000000000000000000000000002440000000000000084'
                    . '000000000000000400000000000000000000000000000000000000000000008400000000000000040050000000000000'
                    . '000000040000000000000004000000000000008400000000000000040000000000000004000000000000014400000000'
                    . '000000840000000000000004000000000000014400000000000001440000000000000084000000000000000400000000'
                    . '000001440000000000000004000000000000008400000000000000040000000000000004000000000000000400000000'
                    . '0000008400000000000000040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3, 2),
                                array(10, 0, 3, 2),
                                array(10, 10, 3, 2),
                                array(0, 10, 3, 2),
                                array(0, 0, 3, 2)
                            ),
                            array(
                                array(2, 2, 3, 2),
                                array(2, 5, 3, 2),
                                array(5, 5, 3, 2),
                                array(5, 2, 3, 2),
                                array(2, 2, 3, 2)
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiPolygonZMValue' => array(
                'value' => '00c00000060000000100c0000003000000020000000500000000000000000000000000000000400800000000000'
                    . '040000000000000004024000000000000000000000000000040080000000000004000000000000000402400000000000'
                    . '040240000000000004008000000000000400000000000000000000000000000004024000000000000400800000000000'
                    . '040000000000000000000000000000000000000000000000040080000000000004000000000000000000000054000000'
                    . '000000000400000000000000040080000000000004000000000000000400000000000000040140000000000004008000'
                    . '000000000400000000000000040140000000000004014000000000000400800000000000040000000000000004014000'
                    . '000000000400000000000000040080000000000004000000000000000400000000000000040000000000000004008000'
                    . '0000000004000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3, 2),
                                array(10, 0, 3, 2),
                                array(10, 10, 3, 2),
                                array(0, 10, 3, 2),
                                array(0, 0, 3, 2)
                            ),
                            array(
                                array(2, 2, 3, 2),
                                array(2, 5, 3, 2),
                                array(5, 5, 3, 2),
                                array(5, 2, 3, 2),
                                array(2, 2, 3, 2)
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiPolygonValueWithSrid' => array(
                'value' => '0106000020E61000000200000001030000000200000005000000000000000000000000000000000000000000000'
                    . '000002440000000000000000000000000000024400000000000002440000000000000000000000000000024400000000'
                    . '000000000000000000000000005000000000000000000144000000000000014400000000000001C40000000000000144'
                    . '00000000000001C400000000000001C4000000000000014400000000000001C400000000000001440000000000000144'
                    . '001030000000100000005000000000000000000F03F000000000000F03F0000000000000840000000000000F03F00000'
                    . '000000008400000000000000840000000000000F03F0000000000000840000000000000F03F000000000000F03F',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
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
            'xdrMultiPolygonValueWithSrid' => array(
                'value' => '0020000006000010E60000000200000000030000000200000005000000000000000000000000000000004024000'
                    . '000000000000000000000000040240000000000004024000000000000000000000000000040240000000000000000000'
                    . '00000000000000000000000000000000540140000000000004014000000000000401C000000000000401400000000000'
                    . '0401C000000000000401C0000000000004014000000000000401C0000000000004014000000000000401400000000000'
                    . '0000000000300000001000000053FF00000000000003FF000000000000040080000000000003FF000000000000040080'
                    . '0000000000040080000000000003FF000000000000040080000000000003FF00000000000003FF0000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
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
            'ndrMultiPolygonZValueWithSrid' => array(
                'value' => '01060000a0e61000000100000001030000800200000005000000000000000000000000000000000000000000000'
                    . '000000840000000000000244000000000000000000000000000000840000000000000244000000000000024400000000'
                    . '000000840000000000000000000000000000024400000000000000840000000000000000000000000000000000000000'
                    . '000000840050000000000000000000040000000000000004000000000000008400000000000000040000000000000144'
                    . '000000000000008400000000000001440000000000000144000000000000008400000000000001440000000000000004'
                    . '00000000000000840000000000000004000000000000000400000000000000840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiPolygonZValueWithSrid' => array(
                'value' => '00a0000006000010e60000000100800000030000000200000005000000000000000000000000000000004008000'
                    . '000000000402400000000000000000000000000004008000000000000402400000000000040240000000000004008000'
                    . '000000000000000000000000040240000000000004008000000000000000000000000000000000000000000004008000'
                    . '000000000000000054000000000000000400000000000000040080000000000004000000000000000401400000000000'
                    . '040080000000000004014000000000000401400000000000040080000000000004014000000000000400000000000000'
                    . '04008000000000000400000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiPolygonMValueWithSrid' => array(
                'value' => '0106000060e61000000100000001030000400200000005000000000000000000000000000000000000000000000'
                    . '000000840000000000000244000000000000000000000000000000840000000000000244000000000000024400000000'
                    . '000000840000000000000000000000000000024400000000000000840000000000000000000000000000000000000000'
                    . '000000840050000000000000000000040000000000000004000000000000008400000000000000040000000000000144'
                    . '000000000000008400000000000001440000000000000144000000000000008400000000000001440000000000000004'
                    . '00000000000000840000000000000004000000000000000400000000000000840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiPolygonMValueWithSrid' => array(
                'value' => '0060000006000010e60000000100400000030000000200000005000000000000000000000000000000004008000'
                    . '000000000402400000000000000000000000000004008000000000000402400000000000040240000000000004008000'
                    . '000000000000000000000000040240000000000004008000000000000000000000000000000000000000000004008000'
                    . '000000000000000054000000000000000400000000000000040080000000000004000000000000000401400000000000'
                    . '040080000000000004014000000000000401400000000000040080000000000004014000000000000400000000000000'
                    . '04008000000000000400000000000000040000000000000004008000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3),
                                array(10, 0, 3),
                                array(10, 10, 3),
                                array(0, 10, 3),
                                array(0, 0, 3)
                            ),
                            array(
                                array(2, 2, 3),
                                array(2, 5, 3),
                                array(5, 5, 3),
                                array(5, 2, 3),
                                array(2, 2, 3)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiPolygonZMValueWithSrid' => array(
                'value' => '01060000e0e61000000100000001030000c00200000005000000000000000000000000000000000000000000000'
                    . '000000840000000000000004000000000000024400000000000000000000000000000084000000000000000400000000'
                    . '000002440000000000000244000000000000008400000000000000040000000000000000000000000000024400000000'
                    . '000000840000000000000004000000000000000000000000000000000000000000000084000000000000000400500000'
                    . '000000000000000400000000000000040000000000000084000000000000000400000000000000040000000000000144'
                    . '000000000000008400000000000000040000000000000144000000000000014400000000000000840000000000000004'
                    . '000000000000014400000000000000040000000000000084000000000000000400000000000000040000000000000004'
                    . '000000000000008400000000000000040',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3, 2),
                                array(10, 0, 3, 2),
                                array(10, 10, 3, 2),
                                array(0, 10, 3, 2),
                                array(0, 0, 3, 2)
                            ),
                            array(
                                array(2, 2, 3, 2),
                                array(2, 5, 3, 2),
                                array(5, 5, 3, 2),
                                array(5, 2, 3, 2),
                                array(2, 2, 3, 2)
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiPolygonZMValueWithSrid' => array(
                'value' => '00e0000006000010e60000000100c00000030000000200000005000000000000000000000000000000004008000'
                    . '000000000400000000000000040240000000000000000000000000000400800000000000040000000000000004024000'
                    . '000000000402400000000000040080000000000004000000000000000000000000000000040240000000000004008000'
                    . '000000000400000000000000000000000000000000000000000000000400800000000000040000000000000000000000'
                    . '540000000000000004000000000000000400800000000000040000000000000004000000000000000401400000000000'
                    . '040080000000000004000000000000000401400000000000040140000000000004008000000000000400000000000000'
                    . '040140000000000004000000000000000400800000000000040000000000000004000000000000000400000000000000'
                    . '040080000000000004000000000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'MULTIPOLYGON',
                    'value' => array(
                        array(
                            array(
                                array(0, 0, 3, 2),
                                array(10, 0, 3, 2),
                                array(10, 10, 3, 2),
                                array(0, 10, 3, 2),
                                array(0, 0, 3, 2)
                            ),
                            array(
                                array(2, 2, 3, 2),
                                array(2, 5, 3, 2),
                                array(5, 5, 3, 2),
                                array(5, 2, 3, 2),
                                array(2, 2, 3, 2)
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrEmptyGeometryCollectionValue' => array(
                'value' => '010700000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(),
                    'dimension' => null
                )
            ),
            'ndrGeometryCollectionValueWithEmptyPoint' => array(
                'value' => '0107000000010000000101000000000000000000F87F000000000000F87F',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array()
                        ),
                    ),
                    'dimension' => null
                )
            ),
            'ndrGeometryCollectionValue' => array(
                'value' => '01070000000300000001010000000000000000002440000000000000244001010000000000000000003E4000000'
                    . '00000003E400102000000020000000000000000002E400000000000002E4000000000000034400000000000003440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(10, 10)
                        ),
                        array(
                            'type'  => 'POINT',
                            'value' => array(30, 30)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(15, 15),
                                array(20, 20)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'xdrGeometryCollectionValue' => array(
                'value' => '0000000007000000030000000001402400000000000040240000000000000000000001403E000000000000403E0'
                    . '00000000000000000000200000002402E000000000000402E00000000000040340000000000004034000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(10, 10)
                        ),
                        array(
                            'type'  => 'POINT',
                            'value' => array(30, 30)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(15, 15),
                                array(20, 20)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrGeometryCollectionZValue' => array(
                'value' => '0107000080030000000101000080000000000000000000000000000000000000000000000000010200008002000'
                    . '000000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f000000000000f'
                    . '03f010700008002000000010100008000000000000000000000000000000000000000000000000001020000800200000'
                    . '0000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f000000000000f03'
                    . 'f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrGeometryCollectionZValue' => array(
                'value' => '0080000007000000030080000001000000000000000000000000000000000000000000000000008000000200000'
                    . '0020000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff0000000000'
                    . '000008000000700000002008000000100000000000000000000000000000000000000000000000000800000020000000'
                    . '20000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff000000000000'
                    . '0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrGeometryCollectionMValue' => array(
                'value' => '0107000040030000000101000040000000000000000000000000000000000000000000000000010200004002000'
                    . '000000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f000000000000f'
                    . '03f010700004002000000010100004000000000000000000000000000000000000000000000000001020000400200000'
                    . '0000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f000000000000f03'
                    . 'f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrGeometryCollectionMValue' => array(
                'value' => '0040000007000000030040000001000000000000000000000000000000000000000000000000004000000200000'
                    . '0020000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff0000000000'
                    . '000004000000700000002004000000100000000000000000000000000000000000000000000000000400000020000000'
                    . '20000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff000000000000'
                    . '0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrGeometryCollectionZMValue' => array(
                'value' => '01070000c00300000001010000c0000000000000000000000000000000000000000000000000000000000000f03'
                    . 'f01020000c0020000000000000000000000000000000000000000000000000000000000000000000040000000000000f'
                    . '03f000000000000f03f000000000000f03f000000000000084001070000c00200000001010000c000000000000000000'
                    . '0000000000000000000000000000000000000000000104001020000c0020000000000000000000000000000000000000'
                    . '000000000000000000000000000001440000000000000f03f000000000000f03f000000000000f03f000000000000184'
                    . '0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0, 1)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0, 2),
                                array(1, 1, 1, 3)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0, 4)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0, 5),
                                        array(1, 1, 1, 6)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrGeometryCollectionZMValue' => array(
                'value' => '00c00000070000000300c00000010000000000000000000000000000000000000000000000003ff000000000000'
                    . '000c00000020000000200000000000000000000000000000000000000000000000040000000000000003ff0000000000'
                    . '0003ff00000000000003ff0000000000000400800000000000000c00000070000000200c000000100000000000000000'
                    . '0000000000000000000000000000000401000000000000000c0000002000000020000000000000000000000000000000'
                    . '0000000000000000040140000000000003ff00000000000003ff00000000000003ff0000000000000401800000000000'
                    . '0',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0, 1)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0, 2),
                                array(1, 1, 1, 3)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0, 4)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0, 5),
                                        array(1, 1, 1, 6)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrGeometryCollectionValueWithSrid' => array(
                'value' => '0107000020E61000000300000001010000000000000000002440000000000000244001010000000000000000003'
                    . 'E400000000000003E400102000000020000000000000000002E400000000000002E40000000000000344000000000000'
                    . '03440',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(10, 10)
                        ),
                        array(
                            'type'  => 'POINT',
                            'value' => array(30, 30)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(15, 15),
                                array(20, 20)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'xdrGeometryCollectionValueWithSrid' => array(
                'value' => '0020000007000010E6000000030000000001402400000000000040240000000000000000000001403E000000000'
                    . '000403E000000000000000000000200000002402E000000000000402E000000000000403400000000000040340000000'
                    . '00000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(10, 10)
                        ),
                        array(
                            'type'  => 'POINT',
                            'value' => array(30, 30)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(15, 15),
                                array(20, 20)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrGeometryCollectionZValueWithSrid' => array(
                'value' => '01070000a0e61000000300000001010000800000000000000000000000000000000000000000000000000102000'
                    . '08002000000000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f00000'
                    . '0000000f03f0107000080020000000101000080000000000000000000000000000000000000000000000000010200008'
                    . '002000000000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f0000000'
                    . '00000f03f',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrGeometryCollectionZValueWithSrid' => array(
                'value' => '00a0000007000010e60000000300800000010000000000000000000000000000000000000000000000000080000'
                    . '002000000020000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff00'
                    . '000000000000080000007000000020080000001000000000000000000000000000000000000000000000000008000000'
                    . '2000000020000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff0000'
                    . '000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrGeometryCollectionMValueWithSrid' => array(
                'value' => '0107000060e61000000300000001010000400000000000000000000000000000000000000000000000000102000'
                    . '04002000000000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f00000'
                    . '0000000f03f0107000040020000000101000040000000000000000000000000000000000000000000000000010200004'
                    . '002000000000000000000000000000000000000000000000000000000000000000000f03f000000000000f03f0000000'
                    . '00000f03f',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrGeometryCollectionMValueWithSrid' => array(
                'value' => '0060000007000010e60000000300400000010000000000000000000000000000000000000000000000000040000'
                    . '002000000020000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff00'
                    . '000000000000040000007000000020040000001000000000000000000000000000000000000000000000000004000000'
                    . '2000000020000000000000000000000000000000000000000000000003ff00000000000003ff00000000000003ff0000'
                    . '000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0),
                                array(1, 1, 1)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0),
                                        array(1, 1, 1)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrGeometryCollectionZMValueWithSrid' => array(
                'value' => '01070000e0e61000000300000001010000c00000000000000000000000000000000000000000000000000000000'
                    . '00000f03f01020000c002000000000000000000000000000000000000000000000000000000000000000000004000000'
                    . '0000000f03f000000000000f03f000000000000f03f000000000000084001070000c00200000001010000c0000000000'
                    . '000000000000000000000000000000000000000000000000000104001020000c00200000000000000000000000000000'
                    . '00000000000000000000000000000000000001440000000000000f03f000000000000f03f000000000000f03f0000000'
                    . '000001840',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0, 1)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0, 2),
                                array(1, 1, 1, 3)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0, 4)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0, 5),
                                        array(1, 1, 1, 6)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrGeometryCollectionZMValueWithSrid' => array(
                'value' => '00e0000007000010e60000000300c00000010000000000000000000000000000000000000000000000003ff0000'
                    . '00000000000c00000020000000200000000000000000000000000000000000000000000000040000000000000003ff00'
                    . '000000000003ff00000000000003ff0000000000000400800000000000000c00000070000000200c0000001000000000'
                    . '000000000000000000000000000000000000000401000000000000000c00000020000000200000000000000000000000'
                    . '000000000000000000000000040140000000000003ff00000000000003ff00000000000003ff00000000000004018000'
                    . '000000000',
                'expected' => array(
                    'srid'  => 4326,
                    'type'  => 'GEOMETRYCOLLECTION',
                    'value' => array(
                        array(
                            'type'  => 'POINT',
                            'value' => array(0, 0, 0, 1)
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(0, 0, 0, 2),
                                array(1, 1, 1, 3)
                            )
                        ),
                        array(
                            'type'  => 'GEOMETRYCOLLECTION',
                            'value' => array(
                                array(
                                    'type'  => 'POINT',
                                    'value' => array(0, 0, 0, 4)
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(0, 0, 0, 5),
                                        array(1, 1, 1, 6)
                                    ),
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrCircularStringValue' => array(
                'value' => '01080000000300000000000000000000000000000000000000000000000000f03f000000000000f03f000000000'
                    . '00000400000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0),
                        array(1, 1),
                        array(2, 0)
                    ),
                    'dimension' => null
                )
            ),
            'xdrCircularStringValue' => array(
                'value' => '000000000800000003000000000000000000000000000000003ff00000000000003ff0000000000000400000000'
                    . '00000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0),
                        array(1, 1),
                        array(2, 0)
                    ),
                    'dimension' => null
                )
            ),
            'ndrCircularStringZValue' => array(
                'value' => '01080000800300000000000000000000000000000000000000000000000000f03f000000000000f03f000000000'
                    . '000f03f000000000000f03f00000000000000400000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0, 1),
                        array(1, 1, 1),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrCircularStringZValue' => array(
                'value' => '008000000800000003000000000000000000000000000000003ff00000000000003ff00000000000003ff000000'
                    . '00000003ff0000000000000400000000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0, 1),
                        array(1, 1, 1),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrCircularStringMValue' => array(
                'value' => '01080000400300000000000000000000000000000000000000000000000000f03f000000000000f03f000000000'
                    . '000f03f000000000000f03f00000000000000400000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0, 1),
                        array(1, 1, 1),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrCircularStringMValue' => array(
                'value' => '004000000800000003000000000000000000000000000000003ff00000000000003ff00000000000003ff000000'
                    . '00000003ff0000000000000400000000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0, 1),
                        array(1, 1, 1),
                        array(2, 0, 1)
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrCircularStringZMValue' => array(
                'value' => '01080000c00300000000000000000000000000000000000000000000000000f03f0000000000000040000000000'
                    . '000f03f000000000000f03f000000000000f03f000000000000004000000000000000400000000000000000000000000'
                    . '000f03f0000000000000040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0, 1, 2),
                        array(1, 1, 1, 2),
                        array(2, 0, 1, 2)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrCircularStringZMValue' => array(
                'value' => '00c000000800000003000000000000000000000000000000003ff000000000000040000000000000003ff000000'
                    . '00000003ff00000000000003ff00000000000004000000000000000400000000000000000000000000000003ff000000'
                    . '00000004000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CIRCULARSTRING',
                    'value' => array(
                        array(0, 0, 1, 2),
                        array(1, 1, 1, 2),
                        array(2, 0, 1, 2)
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrCompoundCurveValue' => array(
                'value' => '01090000000200000001080000000300000000000000000000000000000000000000000000000000f03f0000000'
                    . '00000f03f000000000000004000000000000000000102000000020000000000000000000040000000000000000000000'
                    . '00000001040000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0),
                                array(1, 1),
                                array(2, 0)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0),
                                array(4, 1)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'xdrCompoundCurveValue' => array(
                'value' => '000000000900000002000000000800000003000000000000000000000000000000003ff00000000000003ff0000'
                    . '000000000400000000000000000000000000000000000000002000000024000000000000000000000000000000040100'
                    . '000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0),
                                array(1, 1),
                                array(2, 0)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0),
                                array(4, 1)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrCompoundCurveZValue' => array(
                'value' => '01090000800200000001080000800300000000000000000000000000000000000000000000000000f03f0000000'
                    . '00000f03f000000000000f03f000000000000f03f00000000000000400000000000000000000000000000f03f0102000'
                    . '080020000000000000000000040000000000000000000000000000000000000000000001040000000000000f03f00000'
                    . '0000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0, 1),
                                array(1, 1, 1),
                                array(2, 0, 1)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0, 0),
                                array(4, 1, 1)
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrCompoundCurveZValue' => array(
                'value' => '008000000900000002008000000800000003000000000000000000000000000000003ff00000000000003ff0000'
                    . '0000000003ff00000000000003ff0000000000000400000000000000000000000000000003ff00000000000000080000'
                    . '0020000000240000000000000000000000000000000000000000000000040100000000000003ff00000000000003ff00'
                    . '00000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0, 1),
                                array(1, 1, 1),
                                array(2, 0, 1)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0, 0),
                                array(4, 1, 1)
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrCompoundCurveMValue' => array(
                'value' => '01090000400200000001080000400300000000000000000000000000000000000000000000000000f03f0000000'
                    . '00000f03f000000000000f03f000000000000f03f00000000000000400000000000000000000000000000f03f0102000'
                    . '040020000000000000000000040000000000000000000000000000000000000000000001040000000000000f03f00000'
                    . '0000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0, 1),
                                array(1, 1, 1),
                                array(2, 0, 1)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0, 0),
                                array(4, 1, 1)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrCompoundCurveMValue' => array(
                'value' => '004000000900000002004000000800000003000000000000000000000000000000003ff00000000000003ff0000'
                    . '0000000003ff00000000000003ff0000000000000400000000000000000000000000000003ff00000000000000040000'
                    . '0020000000240000000000000000000000000000000000000000000000040100000000000003ff00000000000003ff00'
                    . '00000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0, 1),
                                array(1, 1, 1),
                                array(2, 0, 1)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0, 0),
                                array(4, 1, 1)
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrCompoundCurveZMValue' => array(
                'value' => '01090000c00200000001080000c00300000000000000000000000000000000000000000000000000f03f0000000'
                    . '000000040000000000000f03f000000000000f03f000000000000f03f000000000000004000000000000000400000000'
                    . '000000000000000000000f03f000000000000004001020000c0020000000000000000000040000000000000000000000'
                    . '0000000000000000000000000000000000000001040000000000000f03f000000000000f03f000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0, 1, 2),
                                array(1, 1, 1, 2),
                                array(2, 0, 1, 2)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0, 0, 0),
                                array(4, 1, 1, 1)
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrCompoundCurveZMValue' => array(
                'value' => '00c00000090000000200c000000800000003000000000000000000000000000000003ff00000000000004000000'
                    . '0000000003ff00000000000003ff00000000000003ff0000000000000400000000000000040000000000000000000000'
                    . '0000000003ff0000000000000400000000000000000c0000002000000024000000000000000000000000000000000000'
                    . '00000000000000000000000000040100000000000003ff00000000000003ff00000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'COMPOUNDCURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0, 1, 2),
                                array(1, 1, 1, 2),
                                array(2, 0, 1, 2)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(2, 0, 0, 0),
                                array(4, 1, 1, 1)
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrCurvePolygonValue' => array(
                'value' => '010A000000020000000108000000030000000000000000000000000000000000000000000000000008400000000'
                    . '0000008400000000000001C400000000000001C400102000000030000000000000000001C400000000000001C4000000'
                    . '00000002040000000000000204000000000000022400000000000002240',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0),
                                array(3, 3),
                                array(7, 7)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(7, 7),
                                array(8, 8),
                                array(9, 9)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrCurvePolygonCompoundCurveValue' => array(
                'value' => '010a000000010000000109000000020000000108000000030000000000000000000000000000000000000000000'
                    . '0000000f03f000000000000f03f000000000000004000000000000000000102000000030000000000000000000040000'
                    . '0000000000000000000000000f03f000000000000f0bf00000000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0),
                                        array(1, 1),
                                        array(2, 0)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0),
                                        array(1, -1),
                                        array(0, 0)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'xdrCurvePolygonCompoundCurveValue' => array(
                'value' => '000000000a00000001000000000900000002000000000800000003000000000000000000000000000000003ff00'
                    . '000000000003ff0000000000000400000000000000000000000000000000000000002000000034000000000000000000'
                    . '00000000000003ff0000000000000bff000000000000000000000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0),
                                        array(1, 1),
                                        array(2, 0)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0),
                                        array(1, -1),
                                        array(0, 0)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrCurvePolygonZCompoundCurveValue' => array(
                'value' => '010a000080010000000109000080020000000108000080030000000000000000000000000000000000000000000'
                    . '0000000f03f000000000000f03f000000000000f03f000000000000f03f0000000000000040000000000000000000000'
                    . '0000000f03f01020000800300000000000000000000400000000000000000000000000000f03f000000000000f03f000'
                    . '000000000f0bf000000000000f03f00000000000000000000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrCurvePolygonZCompoundCurveValue' => array(
                'value' => '008000000a00000001008000000900000002008000000800000003000000000000000000000000000000003ff00'
                    . '000000000003ff00000000000003ff00000000000003ff0000000000000400000000000000000000000000000003ff00'
                    . '00000000000008000000200000003400000000000000000000000000000003ff00000000000003ff0000000000000bff'
                    . '00000000000003ff0000000000000000000000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrCurvePolygonMCompoundCurveValue' => array(
                'value' => '010a000040010000000109000040020000000108000040030000000000000000000000000000000000000000000'
                    . '0000000f03f000000000000f03f000000000000f03f000000000000f03f0000000000000040000000000000000000000'
                    . '0000000f03f01020000400300000000000000000000400000000000000000000000000000f03f000000000000f03f000'
                    . '000000000f0bf000000000000f03f00000000000000000000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrCurvePolygonMCompoundCurveValue' => array(
                'value' => '004000000a00000001004000000900000002004000000800000003000000000000000000000000000000003ff00'
                    . '000000000003ff00000000000003ff00000000000003ff0000000000000400000000000000000000000000000003ff00'
                    . '00000000000004000000200000003400000000000000000000000000000003ff00000000000003ff0000000000000bff'
                    . '00000000000003ff0000000000000000000000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrCurvePolygonZMCompoundCurveValue' => array(
                'value' => '010a0000c00100000001090000c00200000001080000c0030000000000000000000000000000000000000000000'
                    . '0000000f03f0000000000000040000000000000f03f000000000000f03f000000000000f03f000000000000004000000'
                    . '000000000400000000000000000000000000000f03f000000000000004001020000c0030000000000000000000040000'
                    . '0000000000000000000000000f03f0000000000000040000000000000f03f000000000000f0bf000000000000f03f000'
                    . '000000000f03f00000000000000000000000000000000000000000000f03f0000000000000040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1, 2),
                                        array(1, 1, 1, 2),
                                        array(2, 0, 1, 2)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1, 2),
                                        array(1, -1, 1, 1),
                                        array(0, 0, 1, 2)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrCurvePolygonZMVCompoundCurvealue' => array(
                'value' => '00c000000a0000000100c00000090000000200c000000800000003000000000000000000000000000000003ff00'
                    . '0000000000040000000000000003ff00000000000003ff00000000000003ff0000000000000400000000000000040000'
                    . '0000000000000000000000000003ff0000000000000400000000000000000c0000002000000034000000000000000000'
                    . '00000000000003ff000000000000040000000000000003ff0000000000000bff00000000000003ff00000000000003ff'
                    . '0000000000000000000000000000000000000000000003ff00000000000004000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'CURVEPOLYGON',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1, 2),
                                        array(1, 1, 1, 2),
                                        array(2, 0, 1, 2)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1, 2),
                                        array(1, -1, 1, 1),
                                        array(0, 0, 1, 2)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiCurveValue' => array(
                'value' => '010B000000020000000108000000030000000000000000000000000000000000000000000000000008400000000'
                    . '0000008400000000000001C400000000000001C400102000000030000000000000000001C400000000000001C4000000'
                    . '00000002040000000000000204000000000000022400000000000002240',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'CIRCULARSTRING',
                            'value' => array(
                                array(0, 0),
                                array(3, 3),
                                array(7, 7)
                            )
                        ),
                        array(
                            'type'  => 'LINESTRING',
                            'value' => array(
                                array(7, 7),
                                array(8, 8),
                                array(9, 9)
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrMultiCurveCompoundCurveValue' => array(
                'value' => '010b000000010000000109000000020000000108000000030000000000000000000000000000000000000000000'
                    . '0000000f03f000000000000f03f000000000000004000000000000000000102000000030000000000000000000040000'
                    . '0000000000000000000000000f03f000000000000f0bf00000000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0),
                                        array(1, 1),
                                        array(2, 0)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0),
                                        array(1, -1),
                                        array(0, 0)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'xdrMultiCurveCompoundCurveValue' => array(
                'value' => '000000000b00000001000000000900000002000000000800000003000000000000000000000000000000003ff00'
                    . '000000000003ff0000000000000400000000000000000000000000000000000000002000000034000000000000000000'
                    . '00000000000003ff0000000000000bff000000000000000000000000000000000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0),
                                        array(1, 1),
                                        array(2, 0)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0),
                                        array(1, -1),
                                        array(0, 0)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrMultiCurveZCompoundCurveValue' => array(
                'value' => '010b000080010000000109000080020000000108000080030000000000000000000000000000000000000000000'
                    . '0000000f03f000000000000f03f000000000000f03f000000000000f03f0000000000000040000000000000000000000'
                    . '0000000f03f01020000800300000000000000000000400000000000000000000000000000f03f000000000000f03f000'
                    . '000000000f0bf000000000000f03f00000000000000000000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiCurveZCompoundCurveValue' => array(
                'value' => '008000000b00000001008000000900000002008000000800000003000000000000000000000000000000003ff00'
                    . '000000000003ff00000000000003ff00000000000003ff0000000000000400000000000000000000000000000003ff00'
                    . '00000000000008000000200000003400000000000000000000000000000003ff00000000000003ff0000000000000bff'
                    . '00000000000003ff0000000000000000000000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiCurveMCompoundCurveValue' => array(
                'value' => '010b000040010000000109000040020000000108000040030000000000000000000000000000000000000000000'
                    . '0000000f03f000000000000f03f000000000000f03f000000000000f03f0000000000000040000000000000000000000'
                    . '0000000f03f01020000400300000000000000000000400000000000000000000000000000f03f000000000000f03f000'
                    . '000000000f0bf000000000000f03f00000000000000000000000000000000000000000000f03f',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiCurveMCompoundCurveValue' => array(
                'value' => '004000000b00000001004000000900000002004000000800000003000000000000000000000000000000003ff00'
                    . '000000000003ff00000000000003ff00000000000003ff0000000000000400000000000000000000000000000003ff00'
                    . '00000000000004000000200000003400000000000000000000000000000003ff00000000000003ff0000000000000bff'
                    . '00000000000003ff0000000000000000000000000000000000000000000003ff0000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1),
                                        array(1, 1, 1),
                                        array(2, 0, 1)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1),
                                        array(1, -1, 1),
                                        array(0, 0, 1)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiCurveZMCompoundCurveValue' => array(
                'value' => '010b0000c00100000001090000c00200000001080000c0030000000000000000000000000000000000000000000'
                    . '0000000f03f0000000000000040000000000000f03f000000000000f03f000000000000f03f000000000000004000000'
                    . '000000000400000000000000000000000000000f03f000000000000004001020000c0030000000000000000000040000'
                    . '0000000000000000000000000f03f0000000000000040000000000000f03f000000000000f0bf000000000000f03f000'
                    . '000000000f03f00000000000000000000000000000000000000000000f03f0000000000000040',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1, 2),
                                        array(1, 1, 1, 2),
                                        array(2, 0, 1, 2)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1, 2),
                                        array(1, -1, 1, 1),
                                        array(0, 0, 1, 2)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiCurveZMCompoundCurveValue' => array(
                'value' => '00c000000b0000000100c00000090000000200c000000800000003000000000000000000000000000000003ff00'
                    . '0000000000040000000000000003ff00000000000003ff00000000000003ff0000000000000400000000000000040000'
                    . '0000000000000000000000000003ff0000000000000400000000000000000c0000002000000034000000000000000000'
                    . '00000000000003ff000000000000040000000000000003ff0000000000000bff00000000000003ff00000000000003ff'
                    . '0000000000000000000000000000000000000000000003ff00000000000004000000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTICURVE',
                    'value' => array(
                        array(
                            'type'  => 'COMPOUNDCURVE',
                            'value' => array(
                                array(
                                    'type'  => 'CIRCULARSTRING',
                                    'value' => array(
                                        array(0, 0, 1, 2),
                                        array(1, 1, 1, 2),
                                        array(2, 0, 1, 2)
                                    )
                                ),
                                array(
                                    'type'  => 'LINESTRING',
                                    'value' => array(
                                        array(2, 0, 1, 2),
                                        array(1, -1, 1, 1),
                                        array(0, 0, 1, 2)
                                    )
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrMultiSurfaceValue' => array(
                'value' => '010c00000002000000010a000000010000000109000000020000000108000000030000000000000000000000000'
                    . '0000000000000000000000000f03f000000000000f03f000000000000004000000000000000000102000000030000000'
                    . '0000000000000400000000000000000000000000000f03f000000000000f0bf000000000000000000000000000000000'
                    . '103000000010000000500000000000000000024400000000000002440000000000000244000000000000028400000000'
                    . '00000284000000000000028400000000000002840000000000000244000000000000024400000000000002440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0),
                                                array(1, 1),
                                                array(2, 0)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0),
                                                array(1, -1),
                                                array(0, 0)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10),
                                    array(10, 12),
                                    array(12, 12),
                                    array(12, 10),
                                    array(10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'xdrMultiSurfaceValue' => array(
                'value' => '000000000c00000002000000000a000000010000000009000000020000000008000000030000000000000000000'
                    . '00000000000003ff00000000000003ff0000000000000400000000000000000000000000000000000000002000000034'
                    . '00000000000000000000000000000003ff0000000000000bff0000000000000000000000000000000000000000000000'
                    . '000000003000000010000000540240000000000004024000000000000402400000000000040280000000000004028000'
                    . '00000000040280000000000004028000000000000402400000000000040240000000000004024000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0),
                                                array(1, 1),
                                                array(2, 0)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0),
                                                array(1, -1),
                                                array(0, 0)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10),
                                    array(10, 12),
                                    array(12, 12),
                                    array(12, 10),
                                    array(10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => null
                )
            ),
            'ndrMultiSurfaceZValue' => array(
                'value' => '010c00008002000000010a000080010000000109000080020000000108000080030000000000000000000000000'
                    . '0000000000000000000000000f03f000000000000f03f000000000000f03f000000000000f03f0000000000000040000'
                    . '0000000000000000000000000f03f01020000800300000000000000000000400000000000000000000000000000f03f0'
                    . '00000000000f03f000000000000f0bf000000000000f03f00000000000000000000000000000000000000000000f03f0'
                    . '103000080010000000500000000000000000024400000000000002440000000000000244000000000000024400000000'
                    . '000002840000000000000244000000000000028400000000000002840000000000000244000000000000028400000000'
                    . '0000024400000000000002440000000000000244000000000000024400000000000002440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0, 1),
                                                array(1, 1, 1),
                                                array(2, 0, 1)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0, 1),
                                                array(1, -1, 1),
                                                array(0, 0, 1)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10, 10),
                                    array(10, 12, 10),
                                    array(12, 12, 10),
                                    array(12, 10, 10),
                                    array(10, 10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiSurfaceZValue' => array(
                'value' => '008000000c00000002008000000a000000010080000009000000020080000008000000030000000000000000000'
                    . '00000000000003ff00000000000003ff00000000000003ff00000000000003ff00000000000004000000000000000000'
                    . '00000000000003ff0000000000000008000000200000003400000000000000000000000000000003ff00000000000003'
                    . 'ff0000000000000bff00000000000003ff0000000000000000000000000000000000000000000003ff00000000000000'
                    . '080000003000000010000000540240000000000004024000000000000402400000000000040240000000000004028000'
                    . '000000000402400000000000040280000000000004028000000000000402400000000000040280000000000004024000'
                    . '0000000004024000000000000402400000000000040240000000000004024000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0, 1),
                                                array(1, 1, 1),
                                                array(2, 0, 1)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0, 1),
                                                array(1, -1, 1),
                                                array(0, 0, 1)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10, 10),
                                    array(10, 12, 10),
                                    array(12, 12, 10),
                                    array(12, 10, 10),
                                    array(10, 10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrMultiSurfaceMValue' => array(
                'value' => '010c00004002000000010a000040010000000109000040020000000108000040030000000000000000000000000'
                    . '0000000000000000000000000f03f000000000000f03f000000000000f03f000000000000f03f0000000000000040000'
                    . '0000000000000000000000000f03f01020000400300000000000000000000400000000000000000000000000000f03f0'
                    . '00000000000f03f000000000000f0bf000000000000f03f00000000000000000000000000000000000000000000f03f0'
                    . '103000040010000000500000000000000000024400000000000002440000000000000244000000000000024400000000'
                    . '000002840000000000000244000000000000028400000000000002840000000000000244000000000000028400000000'
                    . '0000024400000000000002440000000000000244000000000000024400000000000002440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0, 1),
                                                array(1, 1, 1),
                                                array(2, 0, 1)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0, 1),
                                                array(1, -1, 1),
                                                array(0, 0, 1)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10, 10),
                                    array(10, 12, 10),
                                    array(12, 12, 10),
                                    array(12, 10, 10),
                                    array(10, 10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrMultiSurfaceMValue' => array(
                'value' => '004000000c00000002004000000a000000010040000009000000020040000008000000030000000000000000000'
                    . '00000000000003ff00000000000003ff00000000000003ff00000000000003ff00000000000004000000000000000000'
                    . '00000000000003ff0000000000000004000000200000003400000000000000000000000000000003ff00000000000003'
                    . 'ff0000000000000bff00000000000003ff0000000000000000000000000000000000000000000003ff00000000000000'
                    . '040000003000000010000000540240000000000004024000000000000402400000000000040240000000000004028000'
                    . '000000000402400000000000040280000000000004028000000000000402400000000000040280000000000004024000'
                    . '0000000004024000000000000402400000000000040240000000000004024000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0, 1),
                                                array(1, 1, 1),
                                                array(2, 0, 1)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0, 1),
                                                array(1, -1, 1),
                                                array(0, 0, 1)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10, 10),
                                    array(10, 12, 10),
                                    array(12, 12, 10),
                                    array(12, 10, 10),
                                    array(10, 10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'ndrMultiSurfaceZMValue' => array(
                'value' => '010c0000c002000000010a0000c00100000001090000c00200000001080000c0030000000000000000000000000'
                    . '0000000000000000000000000f03f0000000000000040000000000000f03f000000000000f03f000000000000f03f000'
                    . '000000000004000000000000000400000000000000000000000000000f03f000000000000004001020000c0030000000'
                    . '0000000000000400000000000000000000000000000f03f0000000000000040000000000000f03f000000000000f0bf0'
                    . '00000000000f03f000000000000f03f00000000000000000000000000000000000000000000f03f00000000000000400'
                    . '1030000c0010000000500000000000000000024400000000000002440000000000000244000000000000024400000000'
                    . '000002440000000000000284000000000000024400000000000002440000000000000284000000000000028400000000'
                    . '000002440000000000000244000000000000028400000000000002440000000000000244000000000000024400000000'
                    . '000002440000000000000244000000000000024400000000000002440',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0, 1, 2),
                                                array(1, 1, 1, 2),
                                                array(2, 0, 1, 2)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0, 1, 2),
                                                array(1, -1, 1, 1),
                                                array(0, 0, 1, 2)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10, 10, 10),
                                    array(10, 12, 10, 10),
                                    array(12, 12, 10, 10),
                                    array(12, 10, 10, 10),
                                    array(10, 10, 10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'xdrMultiSurfaceZMValue' => array(
                'value' => '00c000000c0000000200c000000a0000000100c00000090000000200c0000008000000030000000000000000000'
                    . '00000000000003ff000000000000040000000000000003ff00000000000003ff00000000000003ff0000000000000400'
                    . '0000000000000400000000000000000000000000000003ff0000000000000400000000000000000c0000002000000034'
                    . '00000000000000000000000000000003ff000000000000040000000000000003ff0000000000000bff00000000000003'
                    . 'ff00000000000003ff0000000000000000000000000000000000000000000003ff000000000000040000000000000000'
                    . '0c0000003000000010000000540240000000000004024000000000000402400000000000040240000000000004024000'
                    . '000000000402800000000000040240000000000004024000000000000402800000000000040280000000000004024000'
                    . '000000000402400000000000040280000000000004024000000000000402400000000000040240000000000004024000'
                    . '000000000402400000000000040240000000000004024000000000000',
                'expected' => array(
                    'srid'  => null,
                    'type'  => 'MULTISURFACE',
                    'value' => array(
                        array(
                            'type'  => 'CURVEPOLYGON',
                            'value' => array(
                                array(
                                    'type'  => 'COMPOUNDCURVE',
                                    'value' => array(
                                        array(
                                            'type'  => 'CIRCULARSTRING',
                                            'value' => array(
                                                array(0, 0, 1, 2),
                                                array(1, 1, 1, 2),
                                                array(2, 0, 1, 2)
                                            )
                                        ),
                                        array(
                                            'type'  => 'LINESTRING',
                                            'value' => array(
                                                array(2, 0, 1, 2),
                                                array(1, -1, 1, 1),
                                                array(0, 0, 1, 2)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'type'  => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 10, 10, 10),
                                    array(10, 12, 10, 10),
                                    array(12, 12, 10, 10),
                                    array(12, 10, 10, 10),
                                    array(10, 10, 10, 10)
                                )
                            )
                        )
                    ),
                    'dimension' => 'ZM'
                )
            ),
            'ndrPolyhedralSurfaceZValue' => array(
                'value' => '010f000080050000000103000080010000000500000000000000000000000000000000000000000000000000000'
                    . '000000000000000000000000000000000000000000000144000000000000000000000000000002e40000000000000144'
                    . '000000000000000000000000000002e40000000000000000000000000000000000000000000000000000000000000000'
                    . '001030000800100000005000000000000000000000000000000000000000000000000000000000000000000000000000'
                    . '00000002e40000000000000000000000000000024400000000000002e400000000000000000000000000000244000000'
                    . '000000000000000000000000000000000000000000000000000000000000000000000000000010300008001000000050'
                    . '000000000000000000000000000000000000000000000000000000000000000002440000000000000000000000000000'
                    . '000000000000000002440000000000000000000000000000014400000000000000000000000000000000000000000000'
                    . '014400000000000000000000000000000000000000000000000000103000080010000000500000000000000000024400'
                    . '000000000000000000000000000000000000000000024400000000000002e40000000000000000000000000000024400'
                    . '000000000002e40000000000000144000000000000024400000000000000000000000000000144000000000000024400'
                    . '00000000000000000000000000000000103000080010000000500000000000000000000000000000000002e400000000'
                    . '00000000000000000000000000000000000002e40000000000000144000000000000024400000000000002e400000000'
                    . '00000144000000000000024400000000000002e40000000000000000000000000000000000000000000002e400000000'
                    . '000000000',
                'expected' => array(
                    'type' => 'POLYHEDRALSURFACE',
                    'srid' => null,
                    'value' => array(
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 0, 0),
                                    array(0, 0, 5),
                                    array(0, 15, 5),
                                    array(0, 15, 0),
                                    array(0, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 0, 0),
                                    array(0, 15, 0),
                                    array(10, 15, 0),
                                    array(10, 0, 0),
                                    array(0, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 0, 0),
                                    array(10, 0, 0),
                                    array(10, 0, 5),
                                    array(0, 0, 5),
                                    array(0, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 0, 0),
                                    array(10, 15, 0),
                                    array(10, 15, 5),
                                    array(10, 0, 5),
                                    array(10, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 15, 0),
                                    array(0, 15, 5),
                                    array(10, 15, 5),
                                    array(10, 15, 0),
                                    array(0, 15, 0)
                                )
                            )
                        )
                    ),
                    'dimension' => 'Z'
                )
            ),
            'ndrPolyhedralSurfaceMValue' => array(
                'value' => '010f000040050000000103000040010000000500000000000000000000000000000000000000000000000000000'
                    . '000000000000000000000000000000000000000000000144000000000000000000000000000002e40000000000000144'
                    . '000000000000000000000000000002e40000000000000000000000000000000000000000000000000000000000000000'
                    . '001030000400100000005000000000000000000000000000000000000000000000000000000000000000000000000000'
                    . '00000002e40000000000000000000000000000024400000000000002e400000000000000000000000000000244000000'
                    . '000000000000000000000000000000000000000000000000000000000000000000000000000010300004001000000050'
                    . '000000000000000000000000000000000000000000000000000000000000000002440000000000000000000000000000'
                    . '000000000000000002440000000000000000000000000000014400000000000000000000000000000000000000000000'
                    . '014400000000000000000000000000000000000000000000000000103000040010000000500000000000000000024400'
                    . '000000000000000000000000000000000000000000024400000000000002e40000000000000000000000000000024400'
                    . '000000000002e40000000000000144000000000000024400000000000000000000000000000144000000000000024400'
                    . '00000000000000000000000000000000103000040010000000500000000000000000000000000000000002e400000000'
                    . '00000000000000000000000000000000000002e40000000000000144000000000000024400000000000002e400000000'
                    . '00000144000000000000024400000000000002e40000000000000000000000000000000000000000000002e400000000'
                    . '000000000',
                'expected' => array(
                    'type' => 'POLYHEDRALSURFACE',
                    'srid' => null,
                    'value' => array(
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 0, 0),
                                    array(0, 0, 5),
                                    array(0, 15, 5),
                                    array(0, 15, 0),
                                    array(0, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 0, 0),
                                    array(0, 15, 0),
                                    array(10, 15, 0),
                                    array(10, 0, 0),
                                    array(0, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 0, 0),
                                    array(10, 0, 0),
                                    array(10, 0, 5),
                                    array(0, 0, 5),
                                    array(0, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(10, 0, 0),
                                    array(10, 15, 0),
                                    array(10, 15, 5),
                                    array(10, 0, 5),
                                    array(10, 0, 0)
                                )
                            )
                        ),
                        array(
                            'type' => 'POLYGON',
                            'value' => array(
                                array(
                                    array(0, 15, 0),
                                    array(0, 15, 5),
                                    array(10, 15, 5),
                                    array(10, 15, 0),
                                    array(0, 15, 0)
                                )
                            )
                        )
                    ),
                    'dimension' => 'M'
                )
            ),
            'xdrGeometryCollectionValue2' => array(
                'value'    => '01070000000600000001010000000000000000000000000000000000f03f010200000002000000000000000000004000000000000008400000000000001040000000000000144001030000000200000005000000000000000000000000000000000000000000000000000000000000000000244000000000000024400000000000002440000000000000244000000000000000000000000000000000000000000000000005000000000000000000f03f000000000000f03f000000000000f03f0000000000002240000000000000224000000000000022400000000000002240000000000000f03f000000000000f03f000000000000f03f01040000000200000001010000000000000000000000000000000000f03f0101000000000000000000004000000000000008400105000000020000000102000000020000000000000000000000000000000000f03f000000000000004000000000000008400102000000020000000000000000001040000000000000144000000000000018400000000000001c4001060000000200000001030000000200000005000000000000000000000000000000000000000000000000000000000000000000244000000000000024400000000000002440000000000000244000000000000000000000000000000000000000000000000005000000000000000000f03f000000000000f03f000000000000f03f0000000000002240000000000000224000000000000022400000000000002240000000000000f03f000000000000f03f000000000000f03f0103000000010000000500000000000000000022c0000000000000000000000000000022c00000000000002440000000000000f0bf0000000000002440000000000000f0bf000000000000000000000000000022c00000000000000000',
                'expected' => array(
                    'type'      => 'GEOMETRYCOLLECTION',
                    'srid'      => null,
                    'value'     => array(
                            array(
                                'type'  => 'POINT',
                                'value' => array(0, 1)
                            ),
                            array(
                                'type'  => 'LINESTRING',
                                'value' => array(
                                    array(2, 3),
                                    array(4, 5)
                                )
                            ),
                            array(
                                'type'  => 'POLYGON',
                                'value' => array(
                                    array(
                                        array(0, 0),
                                        array(0, 10),
                                        array(10, 10),
                                        array(10, 0),
                                        array(0, 0)
                                    ),
                                    array(
                                        array(1, 1),
                                        array(1, 9),
                                        array(9, 9),
                                        array(9, 1),
                                        array(1, 1)
                                    )
                                )
                            ),
                            array(
                                'type'  => 'MULTIPOINT',
                                'value' => array(
                                    array(0, 1),
                                    array(2, 3)
                                )
                            ),
                            array(
                                'type'  => 'MULTILINESTRING',
                                'value' => array(
                                    array(
                                        array(0, 1),
                                        array(2, 3)
                                    ),
                                    array(
                                        array(4, 5),
                                        array(6, 7)
                                    )
                                )
                            ),
                            array(
                                'type'  => 'MULTIPOLYGON',
                                'value' => array(
                                    array(
                                        array(
                                            array(0, 0),
                                            array(0, 10),
                                            array(10, 10),
                                            array(10, 0),
                                            array(0, 0)
                                        ),
                                        array(
                                            array(1, 1),
                                            array(1, 9),
                                            array(9, 9),
                                            array(9, 1),
                                            array(1, 1))
                                    ),
                                    array(
                                        array(
                                            array(-9, 0),
                                            array(-9, 10),
                                            array(-1, 10),
                                            array(-1, 0),
                                            array(-9, 0)
                                        )
                                    )
                                )
                            )
                        ),
                    'dimension' => null
                )
            ),
            'xdrMultiPointValue2' => array(
                'value' => '01040000000200000001010000000000000000000000000000000000f03f010100000000000000000000400000000000000840',
                'expected' => array(
                    'type'      => 'MULTIPOINT',
                    'value'     => array(array(0, 1), array(2, 3)),
                    'srid'      => null,
                    'dimension' => null,
                )
            ),
            'xdrMultiLineStringValue2' => array(
                'value' => '0105000000020000000102000000020000000000000000000000000000000000f03f000000000000004000000000000008400102000000020000000000000000001040000000000000144000000000000018400000000000001c40',
                'expected' => array(
                    'type'      => 'MULTILINESTRING',
                    'value'     => array(array(array(0, 1), array(2, 3)), array(array(4, 5), array(6, 7))),
                    'srid'      => null,
                    'dimension' => null,
                )
            ),
            'xdrMultiPolygonValue2' => array(
                'value' => '01060000000200000001030000000200000005000000000000000000000000000000000000000000000000000000000000000000244000000000000024400000000000002440000000000000244000000000000000000000000000000000000000000000000005000000000000000000f03f000000000000f03f000000000000f03f0000000000002240000000000000224000000000000022400000000000002240000000000000f03f000000000000f03f000000000000f03f0103000000010000000500000000000000000022c0000000000000000000000000000022c00000000000002440000000000000f0bf0000000000002440000000000000f0bf000000000000000000000000000022c00000000000000000',
                'expected' => array(
                    'type'      => 'MULTIPOLYGON',
                    'value'     => array(array(array(array(0,0), array(0,10), array(10,10), array(10,0), array(0,0)), array(array(1,1), array(1,9), array(9,9), array(9,1), array(1,1))), array(array(array(-9,0), array(-9,10), array(-1,10), array(-1,0), array(-9,0)))),
                    'srid'      => null,
                    'dimension' => null,
                )
            ),
            'xdrMultiPointZOGCValue' => array(
                'value' => '01ec0300000200000001e90300000000000000000000000000000000f03f000000000000004001e9030000000000000000084000000000000010400000000000001440',
                'expected' => array(
                    'type'      => 'MULTIPOINT',
                    'value'=> array(array(0, 1, 2), array(3, 4, 5)),
                    'srid'      => null,
                    'dimension' => 'Z',
                )
            ),
            'xdrMultiLineStringZOGCValue' => array(
                'value' => '01ed0300000200000001ea030000020000000000000000000000000000000000f03f000000000000004000000000000008400000000000001040000000000000144001ea0300000200000000000000000018400000000000001c400000000000002040000000000000224000000000000024400000000000002640',
                'expected' => array(
                    'type'      => 'MULTILINESTRING',
                    'value'     => array(array(array(0, 1, 2), array(3, 4, 5)), array(array(6, 7, 8), array(9, 10, 11))),
                    'srid'      => null,
                    'dimension' => 'Z'
                )
            ),
            'xdrMultiPolygonZOGCValue' => array(
                'value' => '01ee0300000200000001eb030000020000000500000000000000000000000000000000000000000000000000594000000000000000000000000000002440000000000000594000000000000024400000000000002440000000000000594000000000000024400000000000000000000000000000594000000000000000000000000000000000000000000000594005000000000000000000f03f000000000000f03f0000000000005940000000000000f03f000000000000224000000000000059400000000000002240000000000000224000000000000059400000000000002240000000000000f03f0000000000005940000000000000f03f000000000000f03f000000000000594001eb030000010000000500000000000000000022c00000000000000000000000000000494000000000000022c000000000000024400000000000004940000000000000f0bf00000000000024400000000000004940000000000000f0bf0000000000000000000000000000494000000000000022c000000000000000000000000000004940',
                'expected' => array(
                    'type'      => 'MULTIPOLYGON',
                    'value'     => array(
                        array(
                            array(array(0, 0, 100), array(0, 10, 100), array(10, 10, 100), array(10, 0, 100), array(0, 0, 100)),
                            array(array(1, 1, 100), array(1, 9, 100), array(9, 9, 100), array(9, 1, 100), array(1, 1, 100))
                        ),
                        array(
                            array(array(-9, 0, 50), array(-9, 10, 50), array(-1, 10, 50), array(-1, 0, 50), array(-9, 0, 50))
                        )
                    ),
                    'srid'=>null,
                    'dimension' => 'Z'
                )
            ),
            'xdrPointValue2' => array(
                'value' => '0101000000000000000000f03f0000000000000040',
                'expected' => array(
                    'type'      => 'POINT',
                    'value'     => array(1, 2),
                    'srid'      => null,
                    'dimension' => null
                )
            ),
            'xdrLineStringValue2' => array(
                'value' => '010200000002000000000000000000f03f000000000000004000000000000008400000000000001040',
                'expected' => array(
                    'type'      => 'LINESTRING',
                    'value'     => array(array(1, 2), array(3, 4)),
                    'srid'      => null,
                    'dimension' => null
                )
            ),
            'xdrPolygonValue2' => array(
                'value' => '01030000000200000005000000000000000000000000000000000000000000000000000000000000000000244000000000000024400000000000002440000000000000244000000000000000000000000000000000000000000000000005000000000000000000f03f000000000000f03f000000000000f03f0000000000002240000000000000224000000000000022400000000000002240000000000000f03f000000000000f03f000000000000f03f',
                'expected' => array(
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(array(0, 0), array(0, 10), array(10, 10), array(10, 0), array(0, 0)),
                        array(array(1, 1), array(1, 9), array(9, 9), array(9, 1), array(1, 1))
                    ),
                    'srid'      => null,
                    'dimension' => null
                )
            ),
            'xdrPointZOGCValue2' => array(
                'value' => '01e9030000000000000000f03f00000000000000400000000000000840',
                'expected' => array(
                    'type'      => 'POINT',
                    'value'     => array(1, 2, 3),
                    'srid'      => null,
                    'dimension' => 'Z',
                )
            ),
            'xdrLineStringZOGCValue' => array(
                'value' => '01ea03000002000000000000000000f03f00000000000000400000000000000840000000000000104000000000000014400000000000001840',
                'expected' => array(
                    'type'      => 'LINESTRING',
                    'value'     => array(array(1, 2, 3), array(4, 5, 6)),
                    'srid'      => null,
                    'dimension' => 'Z',
                )
            ),
            'xdrPolygonZOGCValue' => array(
                'value' => '01eb030000020000000500000000000000000000000000000000000000000000000000594000000000000000000000000000002440000000000000594000000000000024400000000000002440000000000000594000000000000024400000000000000000000000000000594000000000000000000000000000000000000000000000594005000000000000000000f03f000000000000f03f0000000000005940000000000000f03f000000000000224000000000000059400000000000002240000000000000224000000000000059400000000000002240000000000000f03f0000000000005940000000000000f03f000000000000f03f0000000000005940',
                'expected' => array(
                    'type'      => 'POLYGON',
                    'value'     => array(
                        array(array(0, 0, 100), array(0, 10, 100), array(10, 10, 100), array(10, 0, 100), array(0, 0, 100)),
                        array(array(1, 1, 100), array(1, 9, 100), array(9, 9, 100), array(9, 1, 100), array(1, 1, 100))
                    ),
                    'srid'      => null,
                    'dimension' => 'Z',
                )
            )
        );
    }
}
