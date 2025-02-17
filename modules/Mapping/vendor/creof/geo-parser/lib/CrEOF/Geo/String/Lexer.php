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

namespace CrEOF\Geo\String;

use Doctrine\Common\Lexer\AbstractLexer;

/**
 * Tokenize geographic coordinates
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class Lexer extends AbstractLexer
{
    const T_NONE         = 1;
    const T_INTEGER      = 2;
    const T_FLOAT        = 4;
    const T_CARDINAL_LAT = 5;
    const T_CARDINAL_LON = 6;
    const T_COMMA        = 7;
    const T_PLUS         = 8;
    const T_MINUS        = 9;
    const T_PERIOD       = 10;
    const T_COLON        = 11;
    const T_APOSTROPHE   = 12;
    const T_QUOTE        = 13;
    const T_DEGREE       = 14;

    /**
     * @param string|null $input
     */
    public function __construct($input = null)
    {
        if (null !== $input) {
            $this->setInput($input);
        }
    }

    /**
     * @param string &$value
     *
     * @return int
     */
    protected function getType(&$value)
    {
        if (is_numeric($value)) {
            $value += 0;

            if (is_int($value)) {
                return self::T_INTEGER;
            }

            return self::T_FLOAT;
        }

        switch ($value) {
            case ':':
                return self::T_COLON;
            case '\'':
            case "\xe2\x80\xb2": // prime
                return self::T_APOSTROPHE;
            case '"':
            case "\xe2\x80\xb3": // double prime
                return self::T_QUOTE;
            case ',':
                return self::T_COMMA;
            case '-':
                return self::T_MINUS;
            case '+':
                return self::T_PLUS;
            case '°':
                return self::T_DEGREE;
            case 'N':
            case 'S':
                return self::T_CARDINAL_LAT;
            case 'E':
            case 'W':
                return self::T_CARDINAL_LON;
            default:
                return self::T_NONE;
        }
    }

    /**
     * @return string[]
     */
    protected function getCatchablePatterns()
    {
        return array(
            '[nesw\'",]',
            '(?:[0-9]+)(?:[\.][0-9]+)?(?:e[+-]?[0-9]+)?'
        );
    }

    /**
     * @return string[]
     */
    protected function getNonCatchablePatterns()
    {
        return array('\s+');
    }
}
