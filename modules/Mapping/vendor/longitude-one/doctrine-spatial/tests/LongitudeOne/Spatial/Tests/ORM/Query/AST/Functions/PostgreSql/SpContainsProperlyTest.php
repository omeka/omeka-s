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

namespace LongitudeOne\Spatial\Tests\ORM\Query\AST\Functions\PostgreSql;

use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * SP_ContainsProperly DQL function tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group dql
 * @group pgsql-only
 *
 * @internal
 * @coversDefaultClass
 */
class SpContainsProperlyTest extends OrmTestCase
{
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testFunctionInPredicate()
    {
        $bigPolygon = $this->persistBigPolygon();
        $this->persistSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE PgSql_ContainsProperly(p.polygon, ST_GeomFromText(:p1)) = true'
            // phpcs:enable
        );

        $query->setParameter('p1', 'LINESTRING(5 5,7 5,7 7,5 7,5 5)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($bigPolygon, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testFunctionInSelect()
    {
        $bigPolygon = $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, PgSql_ContainsProperly(p.polygon, ST_GeomFromText(:p1)) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
            // phpcs: enable
        );

        $query->setParameter('p1', 'LINESTRING(5 5,7 5,7 7,5 7,5 5)', 'string');

        $result = $query->getResult();

        static::assertCount(2, $result);
        static::assertEquals($bigPolygon, $result[0][0]);
        static::assertTrue($result[0][1]);
        static::assertEquals($smallPolygon, $result[1][0]);
        static::assertFalse($result[1][1]);
    }
}
