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
 * ST_LineCrossingDirection DQL function tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group dql
 * @group pgsql-only
 *
 * @internal
 * @coversDefaultClass
 */
class SpLineCrossingDirectionTest extends OrmTestCase
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
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testInPredicate()
    {
        $this->persistLineStringX();
        $lineStringY = $this->persistLineStringY();
        $this->persistLineStringZ();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l WHERE PgSql_LineCrossingDirection(l.lineString, ST_GeomFromText(:p1)) = 1'
            // phpcs:enable
        );

        $query->setParameter('p1', 'LINESTRING(12 6,5 11,8 12,5 15)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($lineStringY, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testInSelect()
    {
        $lineStringX = $this->persistLineStringX();
        $lineStringY = $this->persistLineStringY();
        $lineStringZ = $this->persistLineStringZ();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l, PgSql_LineCrossingDirection(l.lineString, ST_GeomFromText(:p1)) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );

        $query->setParameter('p1', 'LINESTRING(12 6,5 11,8 12,5 15)', 'string');

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($lineStringX, $result[0][0]);
        static::assertEquals(2, $result[0][1]);
        static::assertEquals($lineStringY, $result[1][0]);
        static::assertEquals(1, $result[1][1]);
        static::assertEquals($lineStringZ, $result[2][0]);
        static::assertEquals(-1, $result[2][1]);
    }
}
