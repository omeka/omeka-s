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

namespace LongitudeOne\Spatial\Tests\DBAL\Types;

use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\PHP\Types\Geometry\Polygon;
use LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity;
use LongitudeOne\Spatial\Tests\Fixtures\NoHintGeometryEntity;
use LongitudeOne\Spatial\Tests\Helper\GeometryHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PersistHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * Doctrine GeometryType tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group geometry
 *
 * @internal
 * @coversDefaultClass \LongitudeOne\Spatial\DBAL\Types\GeometryType
 */
class GeometryTypeTest extends OrmTestCase
{
    use GeometryHelperTrait;
    use PersistHelperTrait;
    use PolygonHelperTrait;

    /**
     * Setup the geography type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEOMETRY_ENTITY);
        $this->usesEntity(self::NO_HINT_GEOMETRY_ENTITY);
        parent::setUp();
    }

    /**
     * When I store a bad geometry an Invalid value exception shall be thrown.
     */
    public function testBadGeometryValue(): void
    {
        static::expectException(InvalidValueException::class);
        static::expectExceptionMessage('Geometry column values must implement GeometryInterface');

        $entity = new NoHintGeometryEntity();
        $entity->setGeometry('POINT(0 0)');
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Test to store a line string geometry and retrieve it by its identifier.
     */
    public function testLineStringGeometry(): void
    {
        $entity = $this->persistGeometryStraightLine();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a null geometry and retrieve it by its identifier.
     */
    public function testNullGeometry(): void
    {
        $entity = $this->persistNullGeometry();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to persist a point geometry and retrieve it by its identifier.
     */
    public function testPointGeometry(): void
    {
        $entity = $this->persistGeometryO();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a point geometry with its SRID and retrieve it by its identifier.
     *
     * @group srid
     */
    public function testPointGeometryWithSrid(): void
    {
        $entity = $this->persistGeometryA(200);
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a point geometry without SRID and retrieve it by its identifier.
     *
     * @group srid
     */
    public function testPointGeometryWithZeroSrid(): void
    {
        $entity = $this->persistGeometryA(0);

        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to persist a polygon geometry and retrieve it by its identifier.
     *
     * @throws InvalidValueException when geometries are not valid
     */
    public function testPolygonGeometry(): void
    {
        $entity = new GeometryEntity();
        $rings = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
        ];

        $entity->setGeometry(new Polygon($rings));
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a polygon geometry with SRID and retrieve it by its identifier.
     *
     * @group srid
     */
    public function testPolygonGeometryWithSrid(): void
    {
        $entity = new GeometryEntity();

        $polygon = $this->createBigPolygon();
        $polygon->setSrid(4326);
        $entity->setGeometry($polygon);

        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }
}
