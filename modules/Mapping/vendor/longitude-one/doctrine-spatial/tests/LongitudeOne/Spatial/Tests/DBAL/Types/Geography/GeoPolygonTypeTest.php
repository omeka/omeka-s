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

namespace LongitudeOne\Spatial\Tests\DBAL\Types\Geography;

use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geography\LineString;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use LongitudeOne\Spatial\PHP\Types\Geography\Polygon;
use LongitudeOne\Spatial\Tests\Fixtures\GeoPolygonEntity;
use LongitudeOne\Spatial\Tests\Helper\PersistHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * PolygonType tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group geography
 *
 * @internal
 * @coversDefaultClass \LongitudeOne\Spatial\DBAL\Types\Geography\PolygonType
 */
class GeoPolygonTypeTest extends OrmTestCase
{
    use PersistHelperTrait;

    /**
     * Setup the test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEO_POLYGON_ENTITY);
        parent::setUp();
    }

    /**
     * Test the find by polygon method.
     *
     * @throws InvalidValueException when geometry contains an invalid value
     */
    public function testFindByPolygon()
    {
        $rings = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
        ];
        $entity = new GeoPolygonEntity();

        $entity->setPolygon(new Polygon($rings));
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $result = $this->getEntityManager()
            ->getRepository(get_class($entity))
            ->findByPolygon(new Polygon($rings))
        ;

        static::assertEquals($entity, $result[0]);
    }

    /**
     * Test to store an empty polygon.
     */
    public function testNullPolygon()
    {
        $entity = new GeoPolygonEntity();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $id = $entity->getId();

        $this->getEntityManager()->clear();

        $queryEntity = $this->getEntityManager()->getRepository(self::GEO_POLYGON_ENTITY)->find($id);

        static::assertEquals($entity, $queryEntity);
    }

    /**
     * Test to store a polygon ring.
     *
     * @throws InvalidValueException when geometry contains an invalid value
     */
    public function testPolygonRing()
    {
        $rings = [
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
        ];
        $entity = new GeoPolygonEntity();

        $entity->setPolygon(new Polygon($rings));
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a solid polygon.
     *
     * @throws InvalidValueException when geometry contains an invalid value
     */
    public function testSolidPolygon()
    {
        $rings = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
        ];
        $entity = new GeoPolygonEntity();

        $entity->setPolygon(new Polygon($rings));
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }
}
