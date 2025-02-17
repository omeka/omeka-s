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
 * SC_GeometryType DQL function tests.
 * This function is not issue from the OGC, but it is useful for Database postgresql.
 * It does not return the SQL MM Type ('ST_Linestring', 'ST_Polygon') use Standard\StGeometryType class for this.
 * It returns the type of the geometry as a string. Eg: 'LINESTRING', 'POLYGON', 'MULTIPOINT'.
 *
 * @see https://postgis.net/docs/ST_GeometryType.html
 * @see https://postgis.net/docs/GeometryType.html
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
class SpGeometryTypeTest extends OrmTestCase
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
    public function testStAsText()
    {
        $this->persistStraightLineString();
        $this->persistAngularLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT PgSQL_GeometryType(l.lineString) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
        );
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertIsArray($result[0]);
        static::assertCount(1, $result[0]);
        static::assertSame('LINESTRING', $result[0][1]);
    }
}
