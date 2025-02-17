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

use CrEOF\Geo\WKB\Exception\ExceptionInterface;
use CrEOF\Geo\WKB\Exception\UnexpectedValueException;

/**
 * Parser for WKB/EWKB spatial object data
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
class Parser
{
    const WKB_TYPE_GEOMETRY           = 0;
    const WKB_TYPE_POINT              = 1;
    const WKB_TYPE_LINESTRING         = 2;
    const WKB_TYPE_POLYGON            = 3;
    const WKB_TYPE_MULTIPOINT         = 4;
    const WKB_TYPE_MULTILINESTRING    = 5;
    const WKB_TYPE_MULTIPOLYGON       = 6;
    const WKB_TYPE_GEOMETRYCOLLECTION = 7;
    const WKB_TYPE_CIRCULARSTRING     = 8;
    const WKB_TYPE_COMPOUNDCURVE      = 9;
    const WKB_TYPE_CURVEPOLYGON       = 10;
    const WKB_TYPE_MULTICURVE         = 11;
    const WKB_TYPE_MULTISURFACE       = 12;
    const WKB_TYPE_CURVE              = 13;
    const WKB_TYPE_SURFACE            = 14;
    const WKB_TYPE_POLYHEDRALSURFACE  = 15;
    const WKB_TYPE_TIN                = 16;
    const WKB_TYPE_TRIANGLE           = 17;

    const WKB_FLAG_SRID               = 0x20000000;
    const WKB_FLAG_M                  = 0x40000000;
    const WKB_FLAG_Z                  = 0x80000000;

    const TYPE_GEOMETRY           = 'Geometry';
    const TYPE_POINT              = 'Point';
    const TYPE_LINESTRING         = 'LineString';
    const TYPE_POLYGON            = 'Polygon';
    const TYPE_MULTIPOINT         = 'MultiPoint';
    const TYPE_MULTILINESTRING    = 'MultiLineString';
    const TYPE_MULTIPOLYGON       = 'MultiPolygon';
    const TYPE_GEOMETRYCOLLECTION = 'GeometryCollection';
    const TYPE_CIRCULARSTRING     = 'CircularString';
    const TYPE_COMPOUNDCURVE      = 'CompoundCurve';
    const TYPE_CURVEPOLYGON       = 'CurvePolygon';
    const TYPE_MULTICURVE         = 'MultiCurve';
    const TYPE_MULTISURFACE       = 'MultiSurface';
    const TYPE_POLYHEDRALSURFACE  = 'PolyhedralSurface';
    const TYPE_TIN                = 'Tin';
    const TYPE_TRIANGLE           = 'Triangle';

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $srid;

    /**
     * @var int
     */
    private $pointSize;

    /**
     * @var int
     */
    private $byteOrder;

    /**
     * @var int
     */
    private $dimensions;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param string $input
     *
     * @throws UnexpectedValueException
     */
    public function __construct($input = null)
    {
        $this->reader = new Reader();

        if (null !== $input) {
            $this->reader->load($input);
        }
    }

    /**
     * Parse input data
     *
     * @param string $input
     *
     * @return array
     * @throws UnexpectedValueException
     */
    public function parse($input = null)
    {
        if (null !== $input) {
            $this->reader->load($input);
        }

        return $this->readGeometry();
    }

    /**
     * Parse geometry data
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function readGeometry()
    {
        $this->srid      = null;

        try {
            $this->byteOrder = $this->readByteOrder();
            $this->type      = $this->readType();

            if ($this->hasFlag($this->type, self::WKB_FLAG_SRID)) {
                $this->srid = $this->readSrid();
            }

            $this->dimensions = $this->getDimensions($this->type);
            $this->pointSize  = 2 + strlen($this->getDimensionType($this->dimensions) || '');

            $typeName = $this->getTypeName($this->type);

            return array(
                'type'      => $typeName,
                'srid'      => $this->srid,
                'value'     => $this->$typeName(),
                'dimension' => $this->getDimensionType($this->dimensions)
            );
        } catch (ExceptionInterface $e) {
            throw new $e($e->getMessage() . ' at byte ' . $this->reader->getLastPosition(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Check type for flag
     *
     * @param int $type
     * @param int $flag
     *
     * @return bool
     */
    private function hasFlag($type, $flag)
    {
        return ($type & $flag) === $flag;
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    private function is2D($type)
    {
        return $type < 32;
    }

    /**
     * @param int $type
     *
     * @return int|null
     */
    private function getDimensions($type)
    {
        if ($this->is2D($type)) {
            return null;
        }

        if ($type & (self::WKB_FLAG_SRID | self::WKB_FLAG_M | self::WKB_FLAG_Z)) {
            return $type & (self::WKB_FLAG_M | self::WKB_FLAG_Z);
        }

        return $type - ($type % 1000);
    }

    /**
     * @param int $dimensions
     *
     * @return string
     * @throws UnexpectedValueException
     */
    private function getDimensionType($dimensions)
    {
        if ($this->is2D($dimensions)) {
            return null;
        }

        switch ($dimensions) {
            case (1000):
                //no break
            case (self::WKB_FLAG_Z):
                return 'Z';
            case (2000):
                //no break
            case (self::WKB_FLAG_M):
                return 'M';
            case (3000):
                //no break
            case (self::WKB_FLAG_M | self::WKB_FLAG_Z):
                return 'ZM';
        }

        throw new UnexpectedValueException(sprintf('%s with unsupported dimensions 0x%2$X (%2$d)', $this->getTypeName($this->type), $dimensions));
    }

    /**
     * @param int $type
     *
     * @return int
     */
    private function getDimensionedPrimitive($type)
    {
        if (null === $this->dimensions) {
            return $type;
        }

        if ($this->dimensions & (self::WKB_FLAG_Z | self::WKB_FLAG_M)) {
            return $type | $this->dimensions;
        }

        return $type + $this->dimensions;
    }

    /**
     * @param int $type
     *
     * @return int
     */
    private function getTypePrimitive($type)
    {
        if ($this->is2D($type)) {
            return $type;
        }

        if ($type > 0xFFFF) {
            return $type & 0xFF;
        }

        return $type % 1000;
    }

    /**
     * Get name of data type
     *
     * @param int $type
     *
     * @return string
     * @throws UnexpectedValueException
     */
    private function getTypeName($type)
    {
        switch ($this->getTypePrimitive($type)) {
            case (self::WKB_TYPE_POINT):
                $typeName = self::TYPE_POINT;
                break;
            case (self::WKB_TYPE_LINESTRING):
                $typeName = self::TYPE_LINESTRING;
                break;
            case (self::WKB_TYPE_POLYGON):
                $typeName = self::TYPE_POLYGON;
                break;
            case (self::WKB_TYPE_MULTIPOINT):
                $typeName = self::TYPE_MULTIPOINT;
                break;
            case (self::WKB_TYPE_MULTILINESTRING):
                $typeName = self::TYPE_MULTILINESTRING;
                break;
            case (self::WKB_TYPE_MULTIPOLYGON):
                $typeName = self::TYPE_MULTIPOLYGON;
                break;
            case (self::WKB_TYPE_GEOMETRYCOLLECTION):
                $typeName = self::TYPE_GEOMETRYCOLLECTION;
                break;
            case (self::WKB_TYPE_CIRCULARSTRING):
                $typeName = self::TYPE_CIRCULARSTRING;
                break;
            case (self::WKB_TYPE_COMPOUNDCURVE):
                $typeName = self::TYPE_COMPOUNDCURVE;
                break;
            case (self::WKB_TYPE_CURVEPOLYGON):
                $typeName = self::TYPE_CURVEPOLYGON;
                break;
            case (self::WKB_TYPE_MULTICURVE):
                $typeName = self::TYPE_MULTICURVE;
                break;
            case (self::WKB_TYPE_MULTISURFACE):
                $typeName = self::TYPE_MULTISURFACE;
                break;
            case (self::WKB_TYPE_POLYHEDRALSURFACE):
                $typeName = self::TYPE_POLYHEDRALSURFACE;
                break;
            default:
                throw new UnexpectedValueException('Unsupported WKB type "' . $this->type . '"');
        }

        return strtoupper($typeName);
    }

    /**
     * Parse data byte order
     *
     * @throws UnexpectedValueException
     */
    private function readByteOrder()
    {
        return $this->reader->readByteOrder();
    }

    /**
     * Parse data type
     *
     * @throws UnexpectedValueException
     */
    private function readType()
    {
        return $this->reader->readLong();
    }

    /**
     * Parse SRID value
     *
     * @throws UnexpectedValueException
     */
    private function readSrid()
    {
        return $this->reader->readLong();
    }

    /**
     * @return int
     * @throws UnexpectedValueException
     */
    private function readCount()
    {
        return $this->reader->readLong();
    }

    /**
     * @param int $count
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function readPoints($count)
    {
        $points = array();

        for ($i = 0; $i < $count; $i++) {
            $points[] = $this->point();
        }

        return $points;
    }

    /**
     * @param int $count
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function readLinearRings($count)
    {
        $rings = array();

        for ($i = 0; $i < $count; $i++) {
            $rings[] = $this->readPoints($this->readCount());
        }

        return $rings;
    }

    /**
     * Parse POINT values
     *
     * @return float[]
     * @throws UnexpectedValueException
     */
    private function point()
    {
        return $this->reader->readFloats($this->pointSize);
    }

    /**
     * Parse LINESTRING value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function lineString()
    {
        return $this->readPoints($this->readCount());
    }

    /**
     * Parse CIRCULARSTRING value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function circularString()
    {
        return $this->readPoints($this->readCount());
    }

    /**
     * Parse POLYGON value
     *
     * @return array[]
     * @throws UnexpectedValueException
     */
    private function polygon()
    {
        return $this->readLinearRings($this->readCount());
    }

    /**
     * Parse MULTIPOINT value
     *
     * @return array[]
     * @throws UnexpectedValueException
     */
    private function multiPoint()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            if ($this->getDimensionedPrimitive(self::WKB_TYPE_POINT) !== $type) {
                throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_MULTIPOINT, array(self::WKB_TYPE_POINT)));
            }

            $values[] = $this->point();
        }

        return $values;
    }

    /**
     * Parse MULTILINESTRING value
     *
     * @return array[]
     * @throws UnexpectedValueException
     */
    private function multiLineString()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            if ($this->getDimensionedPrimitive(self::WKB_TYPE_LINESTRING) !== $type) {
                throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_MULTILINESTRING, array(self::WKB_TYPE_LINESTRING)));
            }

            $values[] = $this->readPoints($this->readCount());
        }

        return $values;
    }

    /**
     * Parse MULTIPOLYGON value
     *
     * @return array[]
     * @throws UnexpectedValueException
     */
    private function multiPolygon()
    {
        $count  = $this->readCount();
        $values = array();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            if ($this->getDimensionedPrimitive(self::WKB_TYPE_POLYGON) !== $type) {
                throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_MULTIPOLYGON, array(self::WKB_TYPE_POLYGON)));
            }

            $values[] = $this->readLinearRings($this->readCount());
        }

        return $values;
    }

    /**
     * Parse COMPOUNDCURVE value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function compoundCurve()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            switch ($type) {
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_LINESTRING)):
                    // no break
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_CIRCULARSTRING)):
                    $value = $this->readPoints($this->readCount());
                    break;
                default:
                    throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_COMPOUNDCURVE, array(self::WKB_TYPE_LINESTRING, self::WKB_TYPE_CIRCULARSTRING)));
            }

            $values[] = array(
                'type'  => $this->getTypeName($type),
                'value' => $value,
            );
        }

        return $values;
    }

    /**
     * Parse CURVEPOLYGON value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function curvePolygon()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            switch ($type) {
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_LINESTRING)):
                    // no break
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_CIRCULARSTRING)):
                    $value = $this->readPoints($this->readCount());
                    break;
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_COMPOUNDCURVE)):
                    $value = $this->compoundCurve();
                    break;
                default:
                    throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_CURVEPOLYGON, array(self::WKB_TYPE_LINESTRING, self::WKB_TYPE_CIRCULARSTRING, self::WKB_TYPE_COMPOUNDCURVE)));
            }

            $values[] = array(
                'type'  => $this->getTypeName($type),
                'value' => $value,
            );
        }

        return $values;
    }

    /**
     * Parse MULTICURVE value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function multiCurve()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            switch ($type) {
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_LINESTRING)):
                    // no break
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_CIRCULARSTRING)):
                    $value = $this->readPoints($this->readCount());
                    break;
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_COMPOUNDCURVE)):
                    $value = $this->compoundCurve();
                    break;
                default:
                    throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_MULTICURVE, array(self::WKB_TYPE_LINESTRING, self::WKB_TYPE_CIRCULARSTRING, self::WKB_TYPE_COMPOUNDCURVE)));
            }

            $values[] = array(
                'type'  => $this->getTypeName($type),
                'value' => $value,
            );
        }

        return $values;
    }

    /**
     * Parse MULTISURFACE value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function multiSurface()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            switch ($type) {
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_POLYGON)):
                    $value = $this->polygon();
                    break;
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_CURVEPOLYGON)):
                    $value = $this->curvePolygon();
                    break;
                default:
                    throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_MULTISURFACE, array(self::WKB_TYPE_POLYGON, self::WKB_TYPE_CURVEPOLYGON)));
            }

            $values[] = array(
                'type'  => $this->getTypeName($type),
                'value' => $value,
            );
        }

        return $values;
    }

    /**
     * Parse POLYHEDRALSURFACE value
     *
     * @return array
     * @throws UnexpectedValueException
     */
    private function polyhedralSurface()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type = $this->readType();

            switch ($type) {
                case ($this->getDimensionedPrimitive(self::WKB_TYPE_POLYGON)):
                    $value = $this->polygon();
                    break;
                // is polygon the only one?
                default:
                    throw new UnexpectedValueException($this->getBadTypeInTypeMessage($type, self::WKB_TYPE_POLYHEDRALSURFACE, array(self::WKB_TYPE_POLYGON)));
            }

            $values[] = array(
                'type'  => $this->getTypeName($type),
                'value' => $value,
            );
        }

        return $values;
    }

    /**
     * Parse GEOMETRYCOLLECTION value
     *
     * @return array[]
     * @throws UnexpectedValueException
     */
    private function geometryCollection()
    {
        $values = array();
        $count  = $this->readCount();

        for ($i = 0; $i < $count; $i++) {
            $this->readByteOrder();

            $type     = $this->readType();
            $typeName = $this->getTypeName($type);

            $values[] = array(
                'type'  => $typeName,
                'value' => $this->$typeName()
            );
        }

        return $values;
    }

    /**
     * @param int   $childType
     * @param int   $parentType
     * @param int[] $expectedTypes
     *
     * @return string
     */
    private function getBadTypeInTypeMessage($childType, $parentType, array $expectedTypes)
    {
        if ($this->type !== $parentType) {
            $parentType = $this->type;
        }

        $message = sprintf(
            ' %s with dimensions 0x%X (%2$d) in %3$s, expected ',
            $this->getTypeName($childType),
            $this->getDimensions($childType),
            $this->getTypeName($parentType)
        );

        if (! in_array($this->getTypePrimitive($childType), $expectedTypes, true)) {
            if (1 === count($expectedTypes)) {
                $message .= $this->getTypeName($expectedTypes[0]);
            } else {
                $last = $this->getTypeName(array_pop($expectedTypes));
                $message .= implode(array_map(array($this, 'getTypeName'), $expectedTypes), ', ') . ' or ' . $last;
            }

            $message = 'Unexpected' . $message . ' with ';
        } else {
            $message = 'Bad' . $message;
        }

        return $message . sprintf('dimensions 0x%X (%1$d)', $this->dimensions);
    }
}
