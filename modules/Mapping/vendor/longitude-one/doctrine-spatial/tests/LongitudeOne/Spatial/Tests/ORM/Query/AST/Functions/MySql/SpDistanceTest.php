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

use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_Distance MyQL function tests.
 * Be careful, MySQL implements ST_Distance, but this function does not respects the OGC Standard.
 * So you should use this specific function.
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
class SpDistanceTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectStDistanceGeometry()
    {
        $pointO = $this->persistPointO();
        $pointA = $this->persistPointA();
        $pointB = $this->persistPointB();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, MySQL_Distance(p.point, ST_GeomFromText(:p1)) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity p'
            // phpcs:enable
        );

        $query->setParameter('p1', 'POINT(0 0)', 'string');

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($pointO, $result[0][0]);
        static::assertEquals(0, $result[0][1]);
        static::assertEquals($pointA, $result[1][0]);
        static::assertEquals(2.23606797749979, $result[1][1]);
        static::assertEquals($pointB, $result[2][0]);
        static::assertEquals(3.605551275463989, $result[2][1]);
    }
}
