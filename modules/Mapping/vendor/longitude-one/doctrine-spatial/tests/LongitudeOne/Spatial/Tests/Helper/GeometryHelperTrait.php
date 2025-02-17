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

use Doctrine\ORM\EntityManagerInterface;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity;

/**
 * GeometryHelperTrait Trait.
 *
 * This helper provides some methods to generates point entities.
 * All of these points are defined in test documentation.
 *
 * Methods beginning with create will store a geo* entity in database.
 *
 * @see /docs/Test.rst
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @method EntityManagerInterface getEntityManager Return the entity interface
 */
trait GeometryHelperTrait
{
    /**
     * Create a geometry point (x y).
     *
     * @param string $name name of the point
     * @param float  $x    coordinate x
     * @param float  $y    coordinate y
     */
    protected static function createGeometryPoint(string $name, float $x, float $y): Point
    {
        try {
            return new Point($x, $y);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create point %s(%d %d): %s', $name, $x, $y, $e->getMessage()));
        }
    }

    /**
     * Create a geometric Point entity from an array of points.
     *
     * @param GeometryInterface $geometry object implementing Geometry interface
     */
    protected function persistGeometry(GeometryInterface $geometry): GeometryEntity
    {
        $entity = new GeometryEntity();
        $entity->setGeometry($geometry);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity;
    }

    /**
     * Create a geometric point at A (1 1).
     *
     * @param int|null $srid Spatial Reference System Identifier
     */
    protected function persistGeometryA(int $srid = null): GeometryEntity
    {
        $point = static::createGeometryPoint('A', 1, 1);
        if (null !== $srid) {
            $point->setSrid($srid);
        }

        return $this->persistGeometry($point);
    }

    /**
     * Create a geometric point E (5 5).
     *
     * @param int|null $srid Spatial Reference System Identifier
     */
    protected function persistGeometryE(int $srid = null): GeometryEntity
    {
        $point = static::createGeometryPoint('E', 5, 5);
        if (null !== $srid) {
            $point->setSrid($srid);
        }

        return $this->persistGeometry($point);
    }

    /**
     * Create a geometric point at origin.
     *
     * @param int|null $srid Spatial Reference System Identifier
     */
    protected function persistGeometryO(int $srid = null): GeometryEntity
    {
        $point = static::createGeometryPoint('O', 0, 0);
        if (null !== $srid) {
            $point->setSrid($srid);
        }

        return $this->persistGeometry($point);
    }

    /**
     * Create a straight linestring in a geometry entity.
     */
    protected function persistGeometryStraightLine(): GeometryEntity
    {
        try {
            $straightLineString = new LineString([
                [1, 1],
                [2, 2],
                [5, 5],
            ]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create linestring Y (1 1, 2 2, 5 5): %s', $e->getMessage()));
        }

        return $this->persistGeometry($straightLineString);
    }

    /**
     * Persist an entity with null as geometry.
     */
    protected function persistNullGeometry(): GeometryEntity
    {
        $entity = new GeometryEntity();
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity;
    }
}
