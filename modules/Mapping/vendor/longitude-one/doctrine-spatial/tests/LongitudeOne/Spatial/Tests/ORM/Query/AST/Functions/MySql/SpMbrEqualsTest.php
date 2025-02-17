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

namespace LongitudeOne\Spatial\Tests\ORM\Query\AST\Functions\MySql;

use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * MBREquals DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 * @group mysql-only
 *
 * @internal
 * @coversDefaultClass
 */
class SpMbrEqualsTest extends OrmTestCase
{
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testMbrEqualsWhereParameter()
    {
        $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $outerPolygon = $this->persistOuterPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE MySql_MBREquals(p.polygon, ST_GeomFromText(:p)) = 1'
        // phpcs:enable
        );

        $query->setParameter('p', 'LINESTRING(5 5,7 7,6 5)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($smallPolygon, $result[0]);
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE MySql_MBREquals(p.polygon, ST_GeomFromText(:p)) = 1'
        // phpcs:enable
        );

        $query->setParameter('p', 'LINESTRING(15 15,17 17)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($outerPolygon, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testSelectMbrEquals()
    {
        $bigPolygon = $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $outerPolygon = $this->persistOuterPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, MySql_MBREquals(p.polygon, ST_GeomFromText(:p1)) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
            // phpcs:enable
        );

        $query->setParameter('p1', 'LINESTRING(5 5,7 7,6 5)', 'string');

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($bigPolygon, $result[0][0]);
        static::assertEquals(0, $result[0][1]);
        static::assertEquals($smallPolygon, $result[1][0]);
        static::assertEquals(1, $result[1][1]);
        static::assertEquals($outerPolygon, $result[2][0]);
        static::assertEquals(0, $result[2][1]);
    }
}
