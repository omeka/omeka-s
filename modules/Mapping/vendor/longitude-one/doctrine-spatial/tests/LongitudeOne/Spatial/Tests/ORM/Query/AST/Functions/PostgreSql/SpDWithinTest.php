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

use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_DWithin DQL function tests.
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
class SpDWithinTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
        $this->usesEntity(self::GEOGRAPHY_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectGeography()
    {
        $newYork = $this->persistNewYorkGeography();
        $losAngeles = $this->persistLosAngelesGeography();
        $dallas = $this->persistDallasGeography();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT g, PgSql_DWithin(g.geography, PgSql_GeographyFromText(:p), :d, :spheroid) FROM LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity g'
            // phpcs:enable
        );

        $query->setParameter('p', 'POINT(-89.4 43.066667)', 'string');
        $query->setParameter('d', 2000000.0); //2.000.000m=2.000km
        $query->setParameter('spheroid', true, 'boolean');

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($newYork, $result[0][0]);
        static::assertTrue($result[0][1]);
        static::assertEquals($losAngeles, $result[1][0]);
        static::assertFalse($result[1][1]);
        static::assertEquals($dallas, $result[2][0]);
        static::assertTrue($result[2][1]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectGeometry()
    {
        $newYork = $this->persistNewYorkGeometry();
        $losAngeles = $this->persistLosAngelesGeometry();
        $dallas = $this->persistDallasGeometry();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, PgSql_DWithin(p.point, ST_GeomFromText(:p), :d) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity p'
            // phpcs:enable
        );

        $query->setParameter('p', 'POINT(-89.4 43.066667)', 'string');
        $query->setParameter('d', 20.0);

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($newYork, $result[0][0]);
        static::assertTrue($result[0][1]);
        static::assertEquals($losAngeles, $result[1][0]);
        static::assertFalse($result[1][1]);
        static::assertEquals($dallas, $result[2][0]);
        static::assertTrue($result[2][1]);
    }
}
