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

use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_SRID DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StSridTest extends OrmTestCase
{
    use LineStringHelperTrait;
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
        $this->usesEntity(self::GEOGRAPHY_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testFunctionWithGeography()
    {
        $this->persistGeographyLosAngeles();

        $query = $this->getEntityManager()->createQuery(
            'SELECT ST_SRID(g.geography) FROM LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity g'
        );
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertCount(1, $result);
        if ('mysql' == $this->getPlatform()->getName()) {
            //TODO MySQL is returning 0 insteadof 2154
            static::markTestIncomplete('SRID not implemented in Abstraction of MySQL');
        }
        static::assertSame(4326, $result[0][1]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testFunctionWithGeometry()
    {
        $this->persistGeometryPoint('A', 1, 1, 2154);

        $query = $this->getEntityManager()->createQuery(
            'SELECT ST_SRID(g.point) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity g'
        );
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertIsArray($result[0]);
        static::assertCount(1, $result[0]);
        if ('mysql' == $this->getPlatform()->getName()) {
            //TODO MySQL is returning 0 insteadof 2154
            static::markTestIncomplete('SRID not implemented in Abstraction of MySQL');
        }
        static::assertSame(2154, $result[0][1]);
    }
}
