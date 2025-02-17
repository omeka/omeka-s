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

namespace LongitudeOne\Spatial\Tests\ORM\Query\AST\Functions\Standard;

use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_Within DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StWithinTest extends OrmTestCase
{
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testFunctionInPredicate()
    {
        $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $this->persistHoleyPolygon();
        $this->persistPolygonW();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE ST_Within(p.polygon, ST_GeomFromText(:p)) = true'
            // phpcs:enable
        );
        $query->setParameter('p', 'POLYGON((4 4, 4 12, 12 12, 12 4, 4 4))', 'string');
        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($smallPolygon, $result[0]);
    }

    /**
     * Test a DQL containing function to test.
     *
     * @group geometry
     */
    public function testFunctionInSelect()
    {
        $bigPolyon = $this->persistBigPolygon();
        $smallPolygon = $this->persistSmallPolygon();
        $polygonW = $this->persistPolygonW();
        $holeyPolygon = $this->persistHoleyPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, ST_Within(p.polygon, ST_GeomFromText(:p)) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
            // phpcs:enable
        );
        $query->setParameter('p', 'POLYGON((0 0, 0 12, 12 12, 12 0, 0 0))', 'string');
        $result = $query->getResult();

        static::assertCount(4, $result);
        static::assertEquals($bigPolyon, $result[0][0]);
        static::assertEquals(1, $result[0][1]);
        static::assertEquals($smallPolygon, $result[1][0]);
        static::assertEquals(1, $result[1][1]);
        static::assertEquals($polygonW, $result[2][0]);
        static::assertEquals(0, $result[2][1]);
        static::assertEquals($holeyPolygon, $result[3][0]);
        static::assertEquals(1, $result[3][1]);
    }
}
