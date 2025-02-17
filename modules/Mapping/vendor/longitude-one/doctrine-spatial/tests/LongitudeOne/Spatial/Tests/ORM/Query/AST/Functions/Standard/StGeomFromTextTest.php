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
 * ST_GeomFromText DQL function tests.
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
class StGeomFromTextTest extends OrmTestCase
{
    use LineStringHelperTrait;
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
        $this->usesEntity(self::POINT_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select with a linestring.
     *
     * @group geometry
     */
    public function testLineString()
    {
        $lineString = $this->persistStraightLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT g FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity g WHERE g.lineString = ST_GeomFromText(:geometry)'
            // phpcs:enable
        );

        $query->setParameter('geometry', 'LINESTRING(0 0,2 2,5 5)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($lineString, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select with a point.
     *
     * @group geometry
     */
    public function testPoint()
    {
        $pointA = $this->persistPointA();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT g FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity g WHERE g.point = ST_GeomFromText(:geometry)'
        );

        $query->setParameter('geometry', 'POINT(1 2)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($pointA, $result[0]);
    }
}
