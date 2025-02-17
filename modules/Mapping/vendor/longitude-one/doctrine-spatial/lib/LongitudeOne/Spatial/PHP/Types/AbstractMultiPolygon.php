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
abstract class AbstractMultiPolygon extends AbstractGeometry
{
    /**
     * @var array[]
     */
    protected array $polygons = [];

    /**
     * AbstractMultiPolygon constructor.
     *
     * @param AbstractPolygon[]|array[] $polygons Polygons
     * @param int|null                  $srid     Spatial Reference System Identifier
     *
     * @throws InvalidValueException when a polygon is invalid
     */
    public function __construct(array $polygons, $srid = null)
    {
        $this
            ->setPolygons($polygons)
            ->setSrid($srid)
        ;
    }

    /**
     * Add a polygon to geometry.
     *
     * @param AbstractPolygon|array[] $polygon polygon to add
     *
     * @throws InvalidValueException when polygon is not an array nor an AbstractPolygon
     */
    public function addPolygon($polygon): self
    {
        if ($polygon instanceof AbstractPolygon) {
            $polygon = $polygon->toArray();
        }

        if (!is_array($polygon)) {
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            throw new InvalidValueException('AbstractMultiPolygon::addPolygon only accepts AbstractPolygon or an array as parameter');
            // phpcs:enable
        }

        $this->polygons[] = $this->validatePolygonValue($polygon);

        return $this;
    }

    /**
     * Polygon getter.
     *
     * @param int $index Index of polygon, use -1 to get last one
     */
    public function getPolygon(int $index): AbstractPolygon
    {
        //TODO replace by a function to be compliant with -1, -2, etc.
        if (-1 == $index) {
            $index = count($this->polygons) - 1;
        }

        $polygonClass = $this->getNamespace().'\Polygon';

        return new $polygonClass($this->polygons[$index], $this->srid);
    }

    /**
     * Polygons getter.
     *
     * @return AbstractPolygon[]
     */
    public function getPolygons()
    {
        $polygons = [];

        for ($i = 0; $i < count($this->polygons); ++$i) {
            $polygons[] = $this->getPolygon($i);
        }

        return $polygons;
    }

    /**
     * Type getter.
     *
     * @return string MultiPolygon
     */
    public function getType()
    {
        return self::MULTIPOLYGON;
    }

    /**
     * Polygon setter.
     *
     * @param AbstractPolygon[] $polygons polygons to set
     *
     * @throws InvalidValueException when a polygon is invalid
     *
     * @return self
     */
    public function setPolygons(array $polygons)
    {
        $this->polygons = $this->validateMultiPolygonValue($polygons);

        return $this;
    }

    /**
     * Convert Polygon into array.
     *
     * @return array[]
     */
    public function toArray()
    {
        return $this->polygons;
    }
}
