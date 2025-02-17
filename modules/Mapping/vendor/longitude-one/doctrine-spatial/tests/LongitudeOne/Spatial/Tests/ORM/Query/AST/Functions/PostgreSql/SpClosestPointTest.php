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

use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * SP_ClosestPoint DQL function tests.
 * This function is not issue from the OGC, but it is useful for Database postgresql.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 * @group pgsql-only
 *
 * @internal
 * @coversDefaultClass
 */
class SpClosestPointTest extends OrmTestCase
{
    use LineStringHelperTrait;
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testFunctionInSelect()
    {
        $straight = $this->persistStraightLineString();
        $lineC = $this->persistLineStringC();
        $ring = $this->persistRingLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l, ST_AsText(PgSql_ClosestPoint(l.lineString, ST_GeomFromText(:p))) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );
        $query->setParameter('p', 'POINT(4 3)');
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertCount(3, $result);
        static::assertEquals($straight, $result[0][0]);
        static::assertSame('POINT(3.5 3.5)', $result[0][1]);
        static::assertEquals($lineC, $result[1][0]);
        static::assertSame('POINT(4.5 2.5)', $result[1][1]);
        static::assertEquals($ring, $result[2][0]);
        static::assertSame('POINT(1 1)', $result[2][1]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testFunctionWithPolygonInSelect()
    {
        $bigPolygon = $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, ST_AsText(PgSql_ClosestPoint(p.polygon, ST_GeomFromText(:p1))) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
        // phpcs:enable
        );

        $query->setParameter('p1', 'POINT(2 2)', 'string');

        $result = $query->getResult();

        static::assertCount(2, $result);
        static::assertEquals($bigPolygon, $result[0][0]);
        static::assertEquals('POINT(2 2)', $result[0][1]);
        static::assertEquals($smallPolygon, $result[1][0]);
        static::assertEquals('POINT(5 5)', $result[1][1]);
    }
}
