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
 * SP_Scale DQL function tests.
 * This function is not issue from the OGC, but it is useful for Database postgresql.
 *
 * @see https://postgis.net/docs/ST_Scale.html
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
class SpScaleTest extends OrmTestCase
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
        $angularLineString = $this->persistAngularLineString();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l, ST_AsText(PgSQL_Scale(l.lineString, :x, :y)) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );
        $query->setParameter('x', 2);
        $query->setParameter('y', 4);
        //TODO Try to solve this issue on Travis Linux
        //SQLSTATE[XX000]: Internal error: 7 ERROR:  parse error - invalid geometry
        //HINT:  "2" <-- parse error at position 2 within geometry
        static::markTestSkipped('On Linux env only, Postgis throw an internal error');
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertCount(2, $result);
        static::assertEquals($straightLineString, $result[0][0]);
        static::assertSame('LINESTRING(0 0,4 8,10 20)', $result[0][1]);
        static::assertEquals($angularLineString, $result[1][0]);
        static::assertEquals('LINESTRING(6 12,8 60,10 88)', $result[1][1]);
    }
}
