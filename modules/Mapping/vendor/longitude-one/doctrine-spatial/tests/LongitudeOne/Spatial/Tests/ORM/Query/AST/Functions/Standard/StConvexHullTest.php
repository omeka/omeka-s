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

use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_ConvexHull DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StConvexHullTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->usesType('point');
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectStConvexHull()
    {
        static::markTestIncomplete('ST_COLLECT should be implement');
        $origin = $this->persistPointO();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT p, ST_AsText(ST_ConvexHull(:p1)) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity p'
        );

        $query->setParameter('p1', 'MULTIPOINT((0 0), (10 10))', 'geometry');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($origin, $result[0][0]);
        static::assertEquals(1, $result[0][1]);
    }
}
