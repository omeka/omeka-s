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
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_Intersection DQL function tests.
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
class StIntersectionTest extends OrmTestCase
{
    use LineStringHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectStIntersection()
    {
        $lineStringA = $this->persistLineStringA();
        $lineStringB = $this->persistLineStringB();
        $lineStringC = $this->persistLineStringC();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l, ST_AsText(ST_Intersection(l.lineString, ST_GeomFromText(:p))) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );

        $query->setParameter('p', 'POINT(0 0)', 'string');

        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($lineStringA, $result[0][0]);
        static::assertEquals('POINT(0 0)', $result[0][1]);
        static::assertEquals($lineStringB, $result[1][0]);
        static::assertEmptyGeometry($result[1][1], $this->getPlatform());
        static::assertEquals($lineStringC, $result[2][0]);
        static::assertEmptyGeometry($result[2][1], $this->getPlatform());
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testStIntersectionWhereParameter()
    {
        $lineStringA = $this->persistLineStringA();
        $this->persistLineStringB();
        $this->persistLineStringC();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l WHERE ST_IsEmpty(ST_Intersection(l.lineString, ST_GeomFromText(:p1))) = false'
            // phpcs:enable
        );

        $query->setParameter('p1', 'POINT(0 0)', 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($lineStringA, $result[0]);
    }
}
