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
 * ST_LineSubstring DQL function tests.
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
class SpLineSubstringTest extends OrmTestCase
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
    public function testPredicate()
    {
        $straightLineString = $this->persistStraightLineString();
        $this->persistAngularLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l WHERE l.lineString = PgSql_LineSubstring(ST_GeomFromText(:p), :start, :end)'
            // phpcs:enable
        );

        $query->setParameter('p', 'LINESTRING(0 0, 2 2, 10 10)', 'string');
        $query->setParameter('start', 0.0);
        $query->setParameter('end', 0.5);

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($straightLineString, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelect()
    {
        $this->persistStraightLineString();
        $this->persistLineStringA();
        $this->persistLineStringB();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT ST_AsText(PgSql_LineSubstring(l.lineString, :start, :end)) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );
        $query->setParameter('start', 0.4);
        $query->setParameter('end', 0.8);
        $result = $query->getResult();

        static::assertEquals('LINESTRING(2 2,4 4)', $result[0][1]);
        static::assertEquals('LINESTRING(4 4,8 8)', $result[1][1]);
        static::assertEquals('LINESTRING(6 6,12 2)', $result[2][1]);
    }
}
