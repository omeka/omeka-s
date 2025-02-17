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

use LongitudeOne\Spatial\Tests\Fixtures\PointEntity;
use LongitudeOne\Spatial\Tests\Helper\PersistHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * Doctrine PointType tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group geometry
 *
 * @internal
 * @coversDefaultClass \LongitudeOne\Spatial\DBAL\Types\Geometry\PointType
 */
class PointTypeTest extends OrmTestCase
{
    use PersistHelperTrait;
    use PointHelperTrait;

    /**
     * Setup the test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
        parent::setUp();
    }

    /**
     * Test to store a point and find it by its geometric.
     */
    public function testFindByPoint()
    {
        $point = static::createPointA();
        $entity = new PointEntity();

        $entity->setPoint($point);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $result = $this->getEntityManager()->getRepository(self::POINT_ENTITY)->findByPoint($point);

        static::assertEquals($entity, $result[0]);
    }

    /**
     * Test to store a null point and find it by its id.
     */
    public function testNullPoint()
    {
        $entity = new PointEntity();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a point and find it by its id.
     */
    public function testPoint()
    {
        $entity = $this->persistPointA();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    //TODO test to find a null geometry
}
