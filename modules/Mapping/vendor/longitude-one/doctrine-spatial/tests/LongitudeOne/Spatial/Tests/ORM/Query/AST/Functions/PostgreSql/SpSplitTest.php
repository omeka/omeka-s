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
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * SP_Split DQL function tests.
 * This function is not issue from the OGC, but it is useful for Database postgresql.
 *
 * @see https://postgis.net/docs/ST_Split.html
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
class SpSplitTest extends OrmTestCase
{
    use LineStringHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
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
        $straightLineString = $this->persistStraightLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l, ST_AsText(PgSQL_Split(l.lineString, ST_GeomFromText(:g))) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );
        $query->setParameter('g', 'POINT (3 3)');
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertCount(1, $result);
        static::assertEquals($straightLineString, $result[0][0]);
        static::assertSame('GEOMETRYCOLLECTION(LINESTRING(0 0,2 2,3 3),LINESTRING(3 3,5 5))', $result[0][1]);
    }
}
