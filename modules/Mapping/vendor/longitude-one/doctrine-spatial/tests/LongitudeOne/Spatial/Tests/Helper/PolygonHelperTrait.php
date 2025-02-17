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

namespace LongitudeOne\Spatial\Tests\Helper;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\Exception\UnsupportedPlatformException;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\PHP\Types\Geometry\Polygon;
use LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity;

/**
 * TestHelperTrait Trait.
 *
 * This helper provides some methods to generates polygons, linestring and point.
 *
 * TODO All of these polygonal geometries will bo defined in test documentation.
 *
 * Methods beginning with create will store a geo* entity in database.
 *
 * @see /docs/Test.rst
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @method EntityManagerInterface getEntityManager the entity interface
 *
 * @internal
 */
trait PolygonHelperTrait
{
    /**
     * Create the BIG Polygon.
     * Square (0 0, 10 10).
     */
    protected function createBigPolygon(): Polygon
    {
        try {
            return $this->createPolygon([
                new LineString([
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]),
            ]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the big polygon (0, 10): %s', $e->getMessage()));
        }
    }

    /**
     * Create an eccentric polygon.
     * Square (6 6, 10 10).
     */
    protected function createEccentricPolygon(): Polygon
    {
        try {
            return $this->createPolygon([new LineString([
                new Point(6, 6),
                new Point(10, 6),
                new Point(10, 10),
                new Point(6, 10),
                new Point(6, 6),
            ])]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the square(6 6, 10 10): %s', $e->getMessage()));
        }
    }

    /**
     * Create the BIG Polygon and persist it in database.
     * Square (0 0, 10 10).
     */
    protected function createEmptyPolygon(): Polygon
    {
        try {
            return $this->createPolygon([]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create an empty polygon: %s', $e->getMessage()));
        }
    }

    /**
     * Create the HOLEY Polygon.
     * (Big polygon minus Small Polygon).
     */
    protected function createHoleyPolygon(): Polygon
    {
        try {
            return $this->createPolygon([
                new LineString([
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]),
                new LineString([
                    new Point(5, 5),
                    new Point(7, 5),
                    new Point(7, 7),
                    new Point(5, 7),
                    new Point(5, 5),
                ]),
            ]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the holey polygon: %s', $e->getMessage()));
        }
    }

    /**
     * Create the Massachusetts state plane US feet geometry.
     *
     * @param bool $forwardSrid forward SRID for creation
     */
    protected function createMassachusettsState(bool $forwardSrid = true): Polygon
    {
        $srid = null;

        if ($forwardSrid) {
            $srid = 2249;
        }

        try {
            return $this->createPolygon([
                new LineString([
                    new Point(743238, 2967416),
                    new Point(743238, 2967450),
                    new Point(743265, 2967450),
                    new Point(743265.625, 2967416),
                    new Point(743238, 2967416),
                ]),
            ], $srid);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the massachusetts polygon: %s', $e->getMessage()));
        }
    }

    /**
     * Create the Outer Polygon and persist it in database.
     * Square (15 15, 17 17).
     */
    protected function createOuterPolygon(): Polygon
    {
        try {
            return $this->createPolygon([
                new LineString([
                    new Point(15, 15),
                    new Point(17, 15),
                    new Point(17, 17),
                    new Point(15, 17),
                    new Point(15, 15),
                ]),
            ]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the outer polygon (15 15, 17 17): %s', $e->getMessage()));
        }
    }

    /**
     * Create the W Polygon.
     */
    protected function createPolygonW(): Polygon
    {
        try {
            return $this->createPolygon([
                new LineString([
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 20),
                    new Point(0, 20),
                    new Point(10, 10),
                    new Point(0, 0),
                ]),
            ]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the W polygon: %s', $e->getMessage()));
        }
    }

    /**
     * Create the SMALL Polygon.
     * SQUARE (5 5, 7 7).
     */
    protected function createSmallPolygon(): Polygon
    {
        try {
            return $this->createPolygon([
                new LineString([
                    new Point(5, 5),
                    new Point(7, 5),
                    new Point(7, 7),
                    new Point(5, 7),
                    new Point(5, 5),
                ]),
            ]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create the small polygon: %s', $e->getMessage()));
        }
    }

    /**
     * Create the BIG Polygon and persist it in database.
     * Square (0 0, 10 10).
     */
    protected function persistBigPolygon(): PolygonEntity
    {
        return $this->persistPolygon($this->createBigPolygon());
    }

    /**
     * Create an eccentric polygon and persist it in database.
     * Square (6 6, 10 10).
     *
     * DO NOT REMOVE THIS UNUSED method, it will be used soon.
     */
    protected function persistEccentricPolygon(): PolygonEntity
    {
        return $this->persistPolygon($this->createEccentricPolygon());
    }

    /**
     * Create the HOLEY Polygon and persist it in database.
     * (Big polygon minus Small Polygon).
     */
    protected function persistHoleyPolygon(): PolygonEntity
    {
        return $this->persistPolygon($this->createHoleyPolygon());
    }

    /**
     * Create the Massachusetts state plane US feet geometry and persist it in database.
     *
     * @param bool $forwardSrid forward SRID for creation
     */
    protected function persistMassachusettsState(bool $forwardSrid = true): PolygonEntity
    {
        return $this->persistPolygon($this->createMassachusettsState($forwardSrid));
    }

    /**
     * Create the Outer Polygon and persist it in database.
     * Square (15 15, 17 17).
     */
    protected function persistOuterPolygon(): PolygonEntity
    {
        return $this->persistPolygon($this->createOuterPolygon());
    }

    /**
     * Create the W Polygon and persist it in database.
     */
    protected function persistPolygonW(): PolygonEntity
    {
        return $this->persistPolygon($this->createPolygonW());
    }

    /**
     * Create the SMALL Polygon and persist it in database.
     * SQUARE (5 5, 7 7).
     */
    protected function persistSmallPolygon(): PolygonEntity
    {
        return $this->persistPolygon($this->createSmallPolygon());
    }

    /**
     * Create a Polygon from an array of linestrings.
     *
     * @param array    $lineStrings the array of linestrings
     * @param int|null $srid        Spatial Reference System Identifier
     *
     * @throws InvalidValueException when geometries are not valid
     */
    private function createPolygon(array $lineStrings, int $srid = null): Polygon
    {
        $polygon = new Polygon($lineStrings);
        if (null !== $srid) {
            $polygon->setSrid($srid);
        }

        return $polygon;
    }

    /**
     * Persist a polygon.
     *
     * @param Polygon $polygon Geometric polygon to persist
     */
    private function persistPolygon(Polygon $polygon): PolygonEntity
    {
        try {
            if (!$this->getEntityManager() instanceof EntityManagerInterface) {
                static::fail('The entity manager is unavailable. Did you miss to create when setting up your test?');
            }

            $polygonEntity = new PolygonEntity();
            $polygonEntity->setPolygon($polygon);

            $this->getEntityManager()->persist($polygonEntity);
            $this->getEntityManager()->flush();
        } catch (ORMException|Exception|UnsupportedPlatformException $e) {
            static::fail(sprintf('Unable to persist polygon: %s', $e->getMessage()));
        }

        return $polygonEntity;
    }
}
