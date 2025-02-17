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
 * SP_MakeBox2D DQL function tests.
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
class SpMakeBox2dTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelect()
    {
        $this->persistNewYorkGeometry(); // Unused fake point
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT t, ST_AsText(PgSql_MakeBox2D(ST_Point(:x1, :y1), ST_Point(:x2, :y2))) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity t'
            // phpcs:enable
        );
        $query->setParameter('x1', 0);
        $query->setParameter('y1', 0);
        $query->setParameter('x2', 4);
        $query->setParameter('y2', 8);

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals('POLYGON((0 0,0 8,4 8,4 0,0 0))', $result[0][1]);
    }
}
