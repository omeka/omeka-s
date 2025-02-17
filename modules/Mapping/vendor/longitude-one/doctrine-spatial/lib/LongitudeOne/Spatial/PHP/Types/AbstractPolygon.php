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

namespace LongitudeOne\Spatial\PHP\Types;

use LongitudeOne\Spatial\Exception\InvalidValueException;

/**
 * Abstract Polygon object for POLYGON spatial types.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 */
abstract class AbstractPolygon extends AbstractGeometry
{
    /**
     * Polygons are rings.
     *
     * @var array[]
     */
    protected $rings = [];

    /**
     * Abstract polygon constructor.
     *
     * @param AbstractLineString[]|array[] $rings the polygons
     * @param int|null                     $srid  Spatial Reference System Identifier
     *
     * @throws InvalidValueException When a ring is invalid
     */
    public function __construct(array $rings, $srid = null)
    {
        $this->setRings($rings)
            ->setSrid($srid)
        ;
    }

    /**
     * Add a polygon to geometry.
     *
     * @param AbstractLineString|array[] $ring Ring to add to geometry
     *
     * @throws InvalidValueException when a ring is invalid
     *
     * @return self
     */
    public function addRing($ring)
    {
        if ($ring instanceof AbstractPolygon) {
            throw new InvalidValueException('You cannot add a Polygon to another one. Use a Multipolygon.');
        }
        $this->rings[] = $this->validateRingValue($ring);

        return $this;
    }

    /**
     * Polygon getter.
     *
     * @param int $index index of polygon, use -1 to get last one
     *
     * @return AbstractLineString
     */
    public function getRing($index)
    {
        if (-1 == $index) {
            $index = count($this->rings) - 1;
        }

        $lineStringClass = $this->getNamespace().'\LineString';

        return new $lineStringClass($this->rings[$index], $this->srid);
    }

    /**
     * Rings getter.
     *
     * @return AbstractLineString[]
     */
    public function getRings()
    {
        $rings = [];

        for ($i = 0; $i < count($this->rings); ++$i) {
            $rings[] = $this->getRing($i);
        }

        return $rings;
    }

    /**
     * Type getter.
     *
     * @return string Polygon
     */
    public function getType()
    {
        return self::POLYGON;
    }

    /**
     * Rings fluent setter.
     *
     * @param AbstractLineString[] $rings Rings to set
     *
     * @throws InvalidValueException when a ring is invalid
     *
     * @return self
     */
    public function setRings(array $rings)
    {
        $this->rings = $this->validatePolygonValue($rings);

        return $this;
    }

    /**
     * Converts rings to array.
     *
     * @return array[]
     */
    public function toArray()
    {
        return $this->rings;
    }
}
