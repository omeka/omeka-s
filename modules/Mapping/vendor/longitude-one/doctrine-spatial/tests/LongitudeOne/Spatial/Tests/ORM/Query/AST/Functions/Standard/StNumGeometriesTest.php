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

use LongitudeOne\Spatial\Tests\Helper\MultiPointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_NumGeometries DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre-tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StNumGeometriesTest extends OrmTestCase
{
    use MultiPointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::MULTIPOINT_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectStNumGeometries()
    {
        $four = $this->persistFourPoints();
        $single = $this->persistSinglePoint();

        $query = $this->getEntityManager()->createQuery(
            'SELECT m, ST_NumGeometries(m.multiPoint) FROM LongitudeOne\Spatial\Tests\Fixtures\MultiPointEntity m'
        );
        $result = $query->getResult();

        static::assertCount(2, $result);
        static::assertEquals($four, $result[0][0]);
        static::assertEquals(4, $result[0][1]);
        static::assertEquals($single, $result[1][0]);
        static::assertEquals(1, $result[1][1]);
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testStNumGeometriesInPredicate()
    {
        $this->persistFourPoints();
        $single = $this->persistSinglePoint();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT m FROM LongitudeOne\Spatial\Tests\Fixtures\MultiPointEntity m WHERE ST_NumGeometries(m.multiPoint) = :p'
            // phpcs:enable
        );
        $query->setParameter('p', 1);
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertCount(1, $result);
        static::assertEquals($single, $result[0]);
    }
}
