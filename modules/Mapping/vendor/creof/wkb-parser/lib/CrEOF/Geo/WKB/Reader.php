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

namespace CrEOF\Geo\WKB;

use CrEOF\Geo\WKB\Exception\RangeException;
use CrEOF\Geo\WKB\Exception\UnexpectedValueException;

/**
 * Reader for spatial WKB values
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class Reader
{
    const WKB_XDR = 0;
    const WKB_NDR = 1;

    /**
     * @var int
     */
    private $byteOrder;

    /**
     * @var string
     */
    private $input;

    /**
     * @var int
     */
    private $position;

    /**
     * @var int
     */
    private $previous;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private static $machineByteOrder;

    /**
     * @param string $input
     *
     * @throws UnexpectedValueException
     */
    public function __construct($input = null)
    {
        if (null !== $input) {
            $this->load($input);
        }
    }

    /**
     * @param string $input
     *
     * @throws UnexpectedValueException
     */
    public function load($input)
    {
        $this->position = 0;
        $this->previous = 0;

        if (ord($input) < 32) {
            $this->input  = $input;
            $this->length = strlen($input);

            return;
        }

        $position = stripos($input, 'x');

        if (false !== $position) {
            $input = substr($input, $position + 1);
        }

        $this->input  = pack('H*', $input);
        $this->length = strlen($this->input);
    }

    /**
     * @return int
     * @throws UnexpectedValueException
     */
    public function readLong()
    {
        $value           = self::WKB_NDR === $this->getByteOrder() ? $this->unpackInput('V') : $this->unpackInput('N');
        $this->previous  = 4;
        $this->position += $this->previous;

        return $value;
    }

    /**
     * @return float
     * @throws UnexpectedValueException
     * @throws RangeException
     *
     * @deprecated use readFloat()
     */
    public function readDouble()
    {
        return $this->readFloat();
    }

    /**
     * @return float
     * @throws RangeException
     * @throws UnexpectedValueException
     */
    public function readFloat()
    {
        $double = $this->unpackInput('d');

        if ($this->getMachineByteOrder() !== $this->getByteOrder()) {
            $double = unpack('dvalue', strrev(pack('d', $double)));
            $double = $double['value'];
        }

        $this->previous  = 8;
        $this->position += $this->previous;

        return $double;
    }

    /**
     * @param int $count
     *
     * @return float[]
     * @throws RangeException
     * @throws UnexpectedValueException
     *
     * @deprecated use readFloats()
     */
    public function readDoubles($count)
    {
        return $this->readFloats($count);
    }

    /**
     * @param int $count
     *
     * @return float[]
     * @throws RangeException
     * @throws UnexpectedValueException
     */
    public function readFloats($count)
    {
        $floats = array();

        for ($i = 0; $i < $count; $i++) {
            $float = $this->readFloat();

            if (! is_nan($float)) {
                $floats[] = $float;
            }
        }

        return $floats;
    }

    /**
     * @return int
     * @throws RangeException
     * @throws UnexpectedValueException
     */
    public function readByteOrder()
    {
        $byteOrder = $this->unpackInput('C');

        $this->previous  = 1;
        $this->position += $this->previous;

        if ($byteOrder >> 1) {
            throw new UnexpectedValueException('Invalid byte order "' . $byteOrder . '"');
        }

        return $this->byteOrder = $byteOrder;
    }

    /**
     * @return int
     */
    public function getCurrentPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getLastPosition()
    {
        return $this->position - $this->previous;
    }

    /**
     * @return int
     * @throws UnexpectedValueException
     */
    private function getByteOrder()
    {
        if (null === $this->byteOrder) {
            throw new UnexpectedValueException('Invalid byte order "unset"');
        }

        return $this->byteOrder;
    }

    /**
     * @param string $format
     *
     * @return array
     * @throws RangeException
     */
    private function unpackInput($format)
    {
        $code = version_compare(PHP_VERSION, '5.5.0-dev', '>=') ? 'a' : 'A';

        try {
            $result = unpack($format . 'result/' . $code . '*input', $this->input);
        } catch (\Exception $e) {
            throw new RangeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $this->input = $result['input'];

        return $result['result'];
    }

    /**
     * @return bool
     */
    private function getMachineByteOrder()
    {
        if (null === self::$machineByteOrder) {
            $result = unpack('S', "\x01\x00");

            self::$machineByteOrder = $result[1] === 1 ? self::WKB_NDR : self::WKB_XDR;
        }

        return self::$machineByteOrder;
    }
}
