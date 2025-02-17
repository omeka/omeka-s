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
 * Contains DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre-tranchant@gmail.com>
 * @license http://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 * @group mysql-only
 *
 * @internal
 * @coversDefaultClass
 */
class SpMbrContainsTest extends OrmTestCase
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
    public function testContainsWhereParameter()
    {
        $bigPolygon = $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $holeyPolygon = $this->persistHoleyPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE MySql_MBRContains(p.polygon, ST_GeomFromText(:p)) = 1'
            // phpcs:enable
        );

        $query->setParameter('p', 'POINT(6 6)', 'string');
        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($bigPolygon, $result[0]);
        static::assertEquals($smallPolygon, $result[1]);
        static::assertEquals($holeyPolygon, $result[2]);
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE MySQL_MBRContains(p.polygon, ST_GeomFromText(:p)) = 1'
            // phpcs:enable
        );
        $query->setParameter('p', 'POINT(2 2)', 'string');
        $result = $query->getResult();

        static::assertCount(2, $result);
        static::assertEquals($bigPolygon, $result[0]);
        static::assertEquals($holeyPolygon, $result[1]);
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testSelectContains()
    {
        $bigPolygon = $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $holeyPolygon = $this->persistHoleyPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, MySql_MBRContains(p.polygon, ST_GeomFromText(:p1)), MySql_MBRContains(p.polygon, ST_GeomFromText(:p2)) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
        // phpcs:enable
        );

        $query->setParameter('p1', 'POINT(2 2)', 'string');
        $query->setParameter('p2', 'POINT(6 6)', 'string');

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($bigPolygon, $result[0][0]);
        static::assertEquals(1, $result[0][1]);
        static::assertEquals(1, $result[0][2]);
        static::assertEquals($smallPolygon, $result[1][0]);
        static::assertEquals(0, $result[1][1]);
        static::assertEquals(1, $result[1][2]);
        static::assertEquals($holeyPolygon, $result[2][0]);
        static::assertEquals(1, $result[2][1]);
        static::assertEquals(1, $result[2][2]);
    }
}
