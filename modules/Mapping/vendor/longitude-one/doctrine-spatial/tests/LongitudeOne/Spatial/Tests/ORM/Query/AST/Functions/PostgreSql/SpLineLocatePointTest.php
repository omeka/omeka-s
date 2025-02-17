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
 * ST_LineLocatePoint DQL function tests.
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
class SpLineLocatePointTest extends OrmTestCase
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
        $this->persistStraightLineString();
        $lineA = $this->persistLineStringA();
        $lineB = $this->persistLineStringB();

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l WHERE PgSql_LineLocatePoint(l.lineString, ST_GeomFromText(:point)) < :percent'
            // phpcs:enable
        );

        $query->setParameter('point', 'POINT(4 3)', 'string');
        $query->setParameter('percent', 0.5);

        $result = $query->getResult();

        static::assertCount(2, $result);
        static::assertEquals($lineA, $result[0]);
        static::assertEquals($lineB, $result[1]);
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
            'SELECT PgSql_LineLocatePoint(l.lineString, :point) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );
        $query->setParameter('point', 'POINT(4 3)');
        $result = $query->getResult();

        static::assertEquals(0.7, $result[0][1]);
        static::assertEquals(0.35, $result[1][1]);
        static::assertEquals(0.4, $result[2][1]);
    }
}
