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

use CrEOF\Geo\WKB\Reader;

/**
 * Reader tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 *
 * @covers \CrEOF\Geo\WKB\Reader
 */
class ReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param mixed  $value
     * @param array  $methods
     * @param string $exception
     * @param string $message
     *
     * @dataProvider badTestData
     */
    public function testBad($value, array $methods, $exception, $message)
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

        $reader = new Reader($value);

        foreach ($methods as $method) {
            $reader->$method();
        }
    }

    /**
     * @param mixed $value
     * @param array $methods
     *
     * @dataProvider goodTestData
     */
    public function testGood($value, array $methods)
    {
        $reader = new Reader($value);

        foreach ($methods as $test) {
            list($method, $argument, $expected) = $test;

            $actual = $reader->$method($argument);

            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @return array[]
     */
    public function badTestData()
    {
        return array(
            'readBinaryBadByteOrder' => array(
                'value'     => pack('H*', '04'),
                'methods'   => array('readByteOrder'),
                'exception' => '\CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Invalid byte order "4"'
            ),
            'readBinaryWithoutByteOrder' => array(
                'value'     => pack('H*', '0101000000'),
                'methods'   => array('readLong'),
                'exception' => '\CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Invalid byte order "unset"'
            ),
            'readHexWithoutByteOrder' => array(
                'value'     => '0101000000',
                'methods'   => array('readLong'),
                'exception' => '\CrEOF\Geo\WKB\Exception\UnexpectedValueException',
                'message'   => 'Invalid byte order "unset"'
            ),
            'readBinaryShortFloat' => array(
                'value'     => pack('H*', '013D0AD'),
                'methods'   => array('readByteOrder', 'readFloat'),
                'exception' => 'CrEOF\Geo\WKB\Exception\RangeException',
                'message'   => '/Type d: not enough input, need 8, have 3$/'
            ),
        );
    }

    /**
     * @return array[]
     */
    public function goodTestData()
    {
        return array(
            'readBinaryByteOrder' => array(
                'value'   => pack('H*', '01'),
                'methods' => array(
                    array('readByteOrder', null, 1)
                )
            ),
            'readHexByteOrder' => array(
                'value'   => '01',
                'methods' => array(
                    array('readByteOrder', null, 1)
                )
            ),
            'readPrefixedHexByteOrder' => array(
                'value'   => '0x01',
                'methods' => array(
                    array('readByteOrder', null, 1)
                )
            ),
            'readNDRBinaryLong' => array(
                'value'   => pack('H*', '0101000000'),
                'methods' => array(
                    array('readByteOrder', null, 1),
                    array('readLong', null, 1)
                )
            ),
            'readXDRBinaryLong' => array(
                'value'   => pack('H*', '0000000001'),
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('readLong', null, 1)
                )
            ),
            'readNDRHexLong' => array(
                'value'   => '0101000000',
                'methods' => array(
                    array('readByteOrder', null, 1),
                    array('readLong', null, 1)
                )
            ),
            'readXDRHexLong' => array(
                'value'   => '0000000001',
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('readLong', null, 1)
                )
            ),
            'readNDRBinaryFloat' => array(
                'value'   => pack('H*', '013D0AD7A3701D4140'),
                'methods' => array(
                    array('readByteOrder', null, 1),
                    array('readFloat', null, 34.23)
                )
            ),
            'readNDRBinaryDouble' => array(
                'value'   => pack('H*', '013D0AD7A3701D4140'),
                'methods' => array(
                    array('readByteOrder', null, 1),
                    array('readDouble', null, 34.23)
                )
            ),
            'readXDRBinaryFloat' => array(
                'value'   => pack('H*', '0040411D70A3D70A3D'),
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('readFloat', null, 34.23)
                )
            ),
            'readNDRHexFloat' => array(
                'value'   => '013D0AD7A3701D4140',
                'methods' => array(
                    array('readByteOrder', null, 1),
                    array('readFloat', null, 34.23)
                )
            ),
            'readXDRHexFloat' => array(
                'value'   => '0040411D70A3D70A3D',
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('readFloat', null, 34.23)
                )
            ),
            'readXDRBinaryFloats' => array(
                'value'   => pack('H*', '0040411D70A3D70A3D40411D70A3D70A3D'),
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('readFloats', 2, array(34.23, 34.23))
                )
            ),
            'readXDRBinaryDoubles' => array(
                'value'   => pack('H*', '0040411D70A3D70A3D40411D70A3D70A3D'),
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('readDoubles', 2, array(34.23, 34.23))
                )
            ),
            'readXDRPosition' => array(
                'value'   => pack('H*', '0040411D70A3D70A3D40411D70A3D70A3D'),
                'methods' => array(
                    array('readByteOrder', null, 0),
                    array('getCurrentPosition', null, 1),
                    array('getLastPosition', null, 0),
                    array('readFloat', null, 34.23),
                    array('getCurrentPosition', null, 9),
                    array('getLastPosition', null, 1),
                    array('readFloat', null, 34.23),
                    array('getCurrentPosition', null, 17),
                    array('getLastPosition', null, 9)
                )
            ),
        );
    }

    public function testReaderReuse()
    {
        $reader = new Reader();

        $value  = '01';
        $value  = pack('H*', $value);

        $reader->load($value);

        $result = $reader->readByteOrder();

        $this->assertEquals(1, $result);

        $value  = '01';

        $reader->load($value);

        $result = $reader->readByteOrder();

        $this->assertEquals(1, $result);

        $value  = '0x01';

        $reader->load($value);

        $result = $reader->readByteOrder();

        $this->assertEquals(1, $result);

        $value  = '0040411D70A3D70A3D';
        $value  = pack('H*', $value);

        $reader->load($value);

        $reader->readByteOrder();

        $result = $reader->readFloat();

        $this->assertEquals(34.23, $result);
    }
}
