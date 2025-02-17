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

namespace CrEOF\Geo\String\Tests;

use CrEOF\Geo\String\Parser;

/**
 * Parser tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $input
     * @param mixed  $expected
     *
     * @dataProvider dataSourceGood
     */
    public function testGoodValues($input, $expected)
    {
        $parser = new Parser($input);

        $value = $parser->parse();

        $this->assertEquals($expected, $value);
    }

    public function testParserReuse()
    {
        $parser = new Parser();

        foreach ($this->dataSourceGood() as $data) {
            $input    = $data['input'];
            $expected = $data['expected'];

            $value = $parser->parse($input);

            $this->assertEquals($expected, $value);
        }
    }

    /**
     * @param string $input
     * @param string $exception
     * @param string $message
     *
     * @dataProvider dataSourceBad
     */
    public function testBadValues($input, $exception, $message)
    {
        $this->setExpectedException($exception, $message);

        $parser = new Parser($input);

        $parser->parse();
    }

    /**
     * @return array[]
     */
    public function dataSourceGood()
    {
        return array(
            array(
                'input'    => 40,
                'expected' => 40
            ),
            array(
                'input'    => '40',
                'expected' => 40
            ),
            array(
                'input'    => '-40',
                'expected' => -40
            ),
            array(
                'input'    => '1E5',
                'expected' => 100000
            ),
            array(
                'input'    => '1e5',
                'expected' => 100000
            ),
            array(
                'input'    => '1e5°',
                'expected' => 100000
            ),
            array(
                'input'    => '40°',
                'expected' => 40
            ),
            array(
                'input'    => '-40°',
                'expected' => -40
            ),
            array(
                'input'    => '40° N',
                'expected' => 40
            ),
            array(
                'input'    => '40° S',
                'expected' => -40
            ),
            array(
                'input'    => '45.24',
                'expected' => 45.24
            ),
            array(
                'input'    => '45.24°',
                'expected' => 45.24
            ),
            array(
                'input'    => '+45.24°',
                'expected' => 45.24
            ),
            array(
                'input'    => '45.24° S',
                'expected' => -45.24
            ),
            array(
                'input'    => '40° 26\' 46" N',
                'expected' => 40.446111111111
            ),
            array(
                'input'    => '40:26S',
                'expected' => -40.43333333333333
            ),
            array(
                'input'    => '79:56:55W',
                'expected' => -79.948611111111
            ),
            array(
                'input'    => '40:26:46N',
                'expected' => 40.446111111111
            ),
            array(
                'input'    => '40° N 79° W',
                'expected' => array(40, -79)
            ),
            array(
                'input'    => '40 79',
                'expected' => array(40, 79)
            ),
            array(
                'input'    => '40° 79°',
                'expected' => array(40, 79)
            ),
            array(
                'input'    => '40, 79',
                'expected' => array(40, 79)
            ),
            array(
                'input'    => '40°, 79°',
                'expected' => array(40, 79)
            ),
            array(
                'input'    => '40° 26\' 46" N 79° 58\' 56" W',
                'expected' => array(40.446111111111, -79.982222222222)
            ),
            array(
                'input'    => '40° 26\' N 79° 58\' W',
                'expected' => array(40.43333333333333, -79.966666666666669)
            ),
            array(
                'input'    => '40.4738° N, 79.553° W',
                'expected' => array(40.4738, -79.553)
            ),
            array(
                'input'    => '40.4738° S, 79.553° W',
                'expected' => array(-40.4738, -79.553)
            ),
            array(
                'input'    => '40° 26.222\' N 79° 58.52\' E',
                'expected' => array(40.437033333333, 79.975333333333)
            ),
            array(
                'input'    => '40°26.222\'N 79°58.52\'E',
                'expected' => array(40.437033333333, 79.975333333333)
            ),
            array(
                'input'    => '40°26.222\' 79°58.52\'',
                'expected' => array(40.437033333333, 79.975333333333)
            ),
            array(
                'input'    => '40.222° -79.5852°',
                'expected' => array(40.222, -79.5852)
            ),
            array(
                'input'    => '40.222°, -79.5852°',
                'expected' => array(40.222, -79.5852)
            ),
            array(
                'input'    => '44°58\'53.9"N 93°19\'25.8"W',
                'expected' => array(44.981638888888888, -93.32383333333334)
            ),
            array(
                'input'    => '44°58\'53.9"N, 93°19\'25.8"W',
                'expected' => array(44.981638888888888, -93.32383333333334)
            ),
            array(
                'input'    => '79:56:55W 40:26:46N',
                'expected' => array(-79.948611111111, 40.446111111111)
            ),
            array(
                'input'    => '79:56:55 W, 40:26:46 N',
                'expected' => array(-79.948611111111, 40.446111111111)
            ),
            array(
                'input'    => '79°56′55″W, 40°26′46″N',
                'expected' => array(-79.948611111111, 40.446111111111)
            )
        );
    }

    /**
     * @return string[]
     */
    public function dataSourceBad()
    {
        return array(
            array(
                'input'     => '-40°N 45°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 5: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "N" in value "-40°N 45°W"'
            ),
            array(
                'input'     => '+40°N 45°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 5: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "N" in value "+40°N 45°W"'
            ),
            array(
                'input'     => '40°N +45°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 6: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "+" in value "40°N +45°W"'
            ),
            array(
                'input'     => '40°N -45W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 6: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "-" in value "40°N -45W"'
            ),
            array(
                'input'     => '40N -45°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 4: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "-" in value "40N -45°W"'
            ),
            array(
                'input'     => '40N 45°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 6: Error: Expected CrEOF\Geo\String\Lexer::T_CARDINAL_LON, got "°" in value "40N 45°W"'
            ),
            array(
                'input'     => '40°N 45°S',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 10: Error: Expected CrEOF\Geo\String\Lexer::T_CARDINAL_LON, got "S" in value "40°N 45°S"'
            ),
            array(
                'input'     => '40°W 45°E',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 10: Error: Expected CrEOF\Geo\String\Lexer::T_CARDINAL_LAT, got "E" in value "40°W 45°E"'
            ),
            array(
                'input'     => '40° 45',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col -1: Error: Expected CrEOF\Geo\String\Lexer::T_APOSTROPHE, got end of string. in value "40° 45"'
            ),
            array(
                'input'     => '40°, 45',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col -1: Error: Expected CrEOF\Geo\String\Lexer::T_DEGREE, got end of string. in value "40°, 45"'
            ),
            array(
                'input'     => '40N 45',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col -1: Error: Expected CrEOF\Geo\String\Lexer::T_CARDINAL_LON, got end of string. in value "40N 45"'
            ),
            array(
                'input'     => '40 45W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 5: Error: Expected end of string, got "W" in value "40 45W"'
            ),
            array(
                'input'     => '-40.757° 45°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 14: Error: Expected end of string, got "W" in value "-40.757° 45°W"'
            ),
            array(
                'input'     => '40.757°N -45.567°W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 10: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "-" in value "40.757°N -45.567°W"'
            ),
            array(
                'input'     => '44°58\'53.9N 93°19\'25.8"W',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 11: Error: Expected CrEOF\Geo\String\Lexer::T_QUOTE, got "N" in value "44°58\'53.9N 93°19\'25.8"W"'
            ),
            array(
                'input'     => '40:26\'',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 5: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "\'" in value "40:26\'"'
            ),
            array(
                'input'     => '132.4432:',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 8: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got ":" in value "132.4432:"'
            ),
            array(
                'input'     => '55:34:22°',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 8: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "°" in value "55:34:22°"'
            ),
            array(
                'input'     => '55:34.22',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 3: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER, got "34.22" in value "55:34.22"'
            ),
            array(
                'input'     => '55#34.22',
                'exception' => 'UnexpectedValueException',
                'message'   => '[Syntax Error] line 0, col 2: Error: Expected CrEOF\Geo\String\Lexer::T_INTEGER or CrEOF\Geo\String\Lexer::T_FLOAT, got "#" in value "55#34.22"'
            ),
            array(
                'input'     => '200N',
                'exception' => 'RangeException',
                'message'   => '[Range Error] Error: Degrees out of range -90 to 90 in value "200N"'
            ),
            array(
                'input'     => '55:200:32',
                'exception' => 'RangeException',
                'message'   => '[Range Error] Error: Minutes greater than 60 in value "55:200:32"'
            ),
            array(
                'input'     => '55:20:99',
                'exception' => 'RangeException',
                'message'   => '[Range Error] Error: Seconds greater than 60 in value "55:20:99"'
            ),
            array(
                'input'     => '55°70.99\'',
                'exception' => 'RangeException',
                'message'   => '[Range Error] Error: Minutes greater than 60 in value "55°70.99\'"'
            )
        );
    }
}
