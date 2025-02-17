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
 * ST_Area DQL function tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StAreaTest extends OrmTestCase
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
    public function testFunctionInPredicat()
    {
        $this->persistBigPolygon();
        $this->persistHoleyPolygon();
        $this->persistPolygonW();
        $smallPolygon = $this->persistSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE ST_Area(p.polygon) < 50'
        );
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
        $this->persistBigPolygon();
        $this->persistHoleyPolygon();
        $this->persistPolygonW();
        $this->persistSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT ST_Area(p.polygon) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
        );
        $result = $query->getResult();

        static::assertEquals(100, $result[0][1]);
        static::assertEquals(96, $result[1][1]);
        static::assertEquals(100, $result[2][1]);
        static::assertEquals(4, $result[3][1]);
    }
}
