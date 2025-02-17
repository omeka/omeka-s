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

namespace LongitudeOne\Spatial\Tests\DBAL\Types\Geometry;

use LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity;
use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * PolygonType tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group geometry
 *
 * @internal
 * @coversDefaultClass \LongitudeOne\Spatial\DBAL\Types\Geometry\PolygonType
 */
class PolygonTypeTest extends OrmTestCase
{
    use LineStringHelperTrait;
    use PolygonHelperTrait;

    /**
     * Setup the test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        parent::setUp();
    }

    /**
     * Test to store a polygon and find it by its geometric.
     */
    public function testFindByPolygon()
    {
        $polygon = $this->createBigPolygon();
        $entity = $this->persistPolygon($polygon);
        $result = $this->getEntityManager()->getRepository(PolygonEntity::class)->findByPolygon($polygon);

        static::assertCount(1, $result);
        static::assertEquals($entity, $result[0]);
    }

    /**
     * Test to store a null polygon and find it by its id.
     */
    public function testNullPolygon()
    {
        $entity = new PolygonEntity();

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $id = $entity->getId();

        $queryEntity = $this->getEntityManager()->getRepository(self::POLYGON_ENTITY)->find($id);

        static::assertEquals($entity, $queryEntity);
    }

    /**
     * Test to store a polygon ring and find it by its id.
     */
    public function testPolygonRing()
    {
        $entity = $this->persistHoleyPolygon();
        $id = $entity->getId();
        $queryEntity = $this->getEntityManager()->getRepository(self::POLYGON_ENTITY)->find($id);

        static::assertEquals($entity, $queryEntity);
    }

    /**
     * Test to store a solid polygon and find it by its id.
     */
    public function testSolidPolygon()
    {
        $entity = $this->persistBigPolygon();
        $id = $entity->getId();
        $queryEntity = $this->getEntityManager()->getRepository(self::POLYGON_ENTITY)->find($id);

        static::assertEquals($entity, $queryEntity);
    }
}
