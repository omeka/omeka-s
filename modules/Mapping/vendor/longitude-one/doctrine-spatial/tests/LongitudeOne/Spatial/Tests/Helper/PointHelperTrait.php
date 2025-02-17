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
use LongitudeOne\Spatial\PHP\Types\Geography\Point as GeographyPoint;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point as GeometryPoint;
use LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity;
use LongitudeOne\Spatial\Tests\Fixtures\PointEntity as GeometryPointEntity;

/**
 * PointHelperTrait Trait.
 *
 * This helper provides some methods to generates point entities.
 *
 * TODO All of these points will be defined in test documentation.
 *
 * Point Origin (0 0)
 * Point A (1 1)
 * Point B (2 2)
 * Point C (3 3)
 * Point D (4 4)
 * Point E (5 5)
 *
 * Methods beginning with create will create a geo* entity in database, but won't store it in database.
 * Methods beginning with persist will store a geo* entity in database.
 *
 * @see /docs/Test.rst
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @method EntityManagerInterface getEntityManager Return the entity interface
 *
 * @internal
 */
trait PointHelperTrait
{
    /**
     * Create Los Angeles geography Point entity.
     */
    protected static function createLosAngelesGeography(): GeographyPoint
    {
        return static::createGeographyPoint('Los Angeles', -118.2430, 34.0522);
    }

    /**
     * Create Los Angeles geometry Point entity.
     */
    protected static function createLosAngelesGeometry(): GeometryPoint
    {
        return static::createGeometryPoint('Los Angeles', -118.2430, 34.0522);
    }

    /**
     * Create Point A (1 1).
     */
    protected static function createPointA(): GeometryPoint
    {
        return static::createGeometryPoint('a', 1, 1);
    }

    /**
     * Create Point B (2 2).
     */
    protected static function createPointB(): GeometryPoint
    {
        return static::createGeometryPoint('B', 2, 2);
    }

    /**
     * Create Point C (3 3).
     */
    protected static function createPointC(): GeometryPoint
    {
        return static::createGeometryPoint('C', 3, 3);
    }

    /**
     * Create Point D (4 4).
     */
    protected static function createPointD(): GeometryPoint
    {
        return static::createGeometryPoint('D', 4, 4);
    }

    /**
     * Create Point E (5 5).
     */
    protected static function createPointE(): GeometryPoint
    {
        return static::createGeometryPoint('E', 5, 5);
    }

    /**
     * Create Point Origin O (0 0).
     */
    protected static function createPointOrigin(): GeometryPoint
    {
        return static::createGeometryPoint('O', 0, 0);
    }

    /**
     * Create Point E (5 5) with SRID.
     *
     * @param int $srid SRID of geometry point E
     */
    protected static function createPointWithSrid(int $srid): GeometryPoint
    {
        try {
            return new GeometryPoint(5, 5, $srid);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create point E (5 5) with srid %d: %s', $srid, $e->getMessage()));
        }
    }

    /**
     * Create a geography point.
     *
     * @param string $name name is only used when an exception is thrown
     * @param float  $x    X coordinate
     * @param float  $y    Y coordinate
     */
    private static function createGeographyPoint(string $name, float $x, float $y): GeographyPoint
    {
        try {
            return new GeographyPoint($x, $y);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create point %s(%d %d): %s', $name, $x, $y, $e->getMessage()));
        }
    }

    /**
     * Create a geometry point.
     *
     * @param string $name name is only used when an exception is thrown
     * @param float  $x    X coordinate
     * @param float  $y    Y coordinate
     */
    private static function createGeometryPoint(string $name, float $x, float $y): GeometryPoint
    {
        try {
            return new GeometryPoint($x, $y);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create point %s(%d %d): %s', $name, $x, $y, $e->getMessage()));
        }
    }

    /**
     * Create New York geography point.
     */
    private static function createNewYorkGeography(): GeographyPoint
    {
        return static::createGeographyPoint('New-York', -73.938611, 40.664167);
    }

    /**
     * Create New York geometry point.
     */
    private static function createNewYorkGeometry(): GeometryPoint
    {
        return static::createGeometryPoint('New-York', -73.938611, 40.664167);
    }

    /**
     * Create Dallas geography Point entity and store it in database.
     */
    protected function persistDallasGeography(): GeographyEntity
    {
        return $this->persistGeography(static::createGeographyPoint('Dallas', -96.803889, 32.782778));
    }

    /**
     * Create Dallas geometry Point entity and store it in database.
     */
    protected function persistDallasGeometry(): GeometryPointEntity
    {
        return $this->persistGeometry(static::createGeometryPoint('Dallas', -96.803889, 32.782778));
    }

    /**
     * Create Paris city in Lambert93 (French SRID) as geometry Point entity and store it in database.
     *
     * @param bool $setSrid initialize the SRID to 2154 if true
     */
    protected function persistGeographyLosAngeles(bool $setSrid = true): GeographyEntity
    {
        $srid = $setSrid ? 4326 : null;

        return $this->persistGeographyPoint('Los Angeles', -118.2430, 34.0522, $srid);
    }

    /**
     * Persist a geometry point (x y).
     *
     * @param string $name name of the point
     * @param float  $x    coordinate x
     * @param float  $y    coordinate y
     * @param ?int   $srid SRID
     */
    protected function persistGeographyPoint(string $name, float $x, float $y, ?int $srid = null): GeographyEntity
    {
        $point = static::createGeographyPoint($name, $x, $y);
        if (null !== $srid) {
            $point->setSrid($srid);
        }

        return $this->persistGeography($point);
    }

    /**
     * Create Paris city in Lambert93 (French SRID) as geometry Point entity and store it in database.
     *
     * @param bool $setSrid initialize the SRID to 2154 if true
     */
    protected function persistGeometryParisLambert93(bool $setSrid = true): GeometryPointEntity
    {
        $srid = $setSrid ? 2154 : null;

        return $this->persistGeometryPoint('Paris', 6519, 68624, $srid);
    }

    /**
     * Persist a geometry point (x y).
     *
     * @param string $name name of the point
     * @param float  $x    coordinate x
     * @param float  $y    coordinate y
     * @param ?int   $srid SRID
     */
    protected function persistGeometryPoint(string $name, float $x, float $y, ?int $srid = null): GeometryPointEntity
    {
        $point = static::createGeometryPoint($name, $x, $y);
        if (null !== $srid) {
            $point->setSrid($srid);
        }

        return $this->persistGeometry($point);
    }

    /**
     * Create Los Angeles geography Point entity and store it in database.
     */
    protected function persistLosAngelesGeography(): GeographyEntity
    {
        return $this->persistGeography(static::createLosAngelesGeography());
    }

    /**
     * Create Los Angeles geometry Point entity and persist it in database.
     */
    protected function persistLosAngelesGeometry(): GeometryPointEntity
    {
        return $this->persistGeometry($this->createLosAngelesGeometry());
    }

    /**
     * Create New York geography Point entity and store it in database.
     */
    protected function persistNewYorkGeography(): GeographyEntity
    {
        return $this->persistGeography(static::createNewYorkGeography());
    }

    /**
     * Create New York geometry Point entity and store it in database.
     */
    protected function persistNewYorkGeometry(): GeometryPointEntity
    {
        return $this->persistGeometry(static::createNewYorkGeometry());
    }

    /**
     * Create and persist the point A (1, 2).
     *
     * @param ?int $srid If srid is missing, no SRID is set
     */
    protected function persistPointA(?int $srid = null): GeometryPointEntity
    {
        return $this->persistGeometryPoint('A', 1, 2, $srid);
    }

    /**
     * Create the point B (-2, 3).
     */
    protected function persistPointB(): GeometryPointEntity
    {
        return $this->persistGeometryPoint('B', -2, 3);
    }

    /**
     * Create the point E (5, 5).
     */
    protected function persistPointE(): GeometryPointEntity
    {
        return $this->persistGeometryPoint('E', 5, 5);
    }

    /**
     * Create the point origin O(0, 0).
     *
     * @param bool $setSrid Set the SRID to zero instead of null
     */
    protected function persistPointO(bool $setSrid = false): GeometryPointEntity
    {
        $srid = $setSrid ? 0 : null;

        return $this->persistGeometryPoint('O', 0, 0, $srid);
    }

    /**
     * Create Tours city in Lambert93 (French SRID) as geometry Point entity and store it in database.
     *
     * @param bool $setSrid initialize the SRID to 2154 if true
     */
    protected function persistToursLambert93(bool $setSrid = true): GeometryPointEntity
    {
        $srid = $setSrid ? 2154 : null;

        return $this->persistGeometryPoint('Tours', 525375.21, 6701871.83, $srid);
    }

    /**
     * Create a geographic Point entity from an array of points.
     *
     * @param GeographyPoint|array $point Point could be an array of X, Y or an instance of Point class
     */
    private function persistGeography(GeographyPoint $point): GeographyEntity
    {
        $pointEntity = new GeographyEntity();
        $pointEntity->setGeography($point);
        $this->getEntityManager()->persist($pointEntity);
        $this->getEntityManager()->flush();

        return $pointEntity;
    }

    /**
     * Create a geometric Point entity from an array of points.
     *
     * @param GeometryPoint|array $point Point could be an array of X, Y or an instance of Point class
     */
    private function persistGeometry(GeometryPoint $point): GeometryPointEntity
    {
        $pointEntity = new GeometryPointEntity();
        $pointEntity->setPoint($point);
        $this->getEntityManager()->persist($pointEntity);
        $this->getEntityManager()->flush();

        return $pointEntity;
    }
}
