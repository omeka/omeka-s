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

use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_PolyFromWkb DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StPolyFromWkbTest extends OrmTestCase
{
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testPredicate()
    {
        $bigPolygon = $this->persistBigPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE p.polygon = ST_PolyFromWkb(:wkb)'
        );
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $query->setParameter('wkb', hex2bin('010300000001000000050000000000000000000000000000000000000000000000000024400000000000000000000000000000244000000000000024400000000000000000000000000000244000000000000000000000000000000000'), 'blob');
        // phpcs:enable

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($bigPolygon, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelect()
    {
        $this->persistBigPolygon(); // Unused fake polygon
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $query = $this->getEntityManager()->createQuery(
            'SELECT p, ST_AsText(ST_PolyFromWkb(:wkb)) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
        );
        $query->setParameter('wkb', hex2bin('010300000001000000050000000000000000000000000000000000000000000000000024400000000000000000000000000000244000000000000024400000000000000000000000000000244000000000000000000000000000000000'), 'blob');
        $result = $query->getResult();
        // phpcs:enable

        static::assertCount(1, $result);
        static::assertEquals('POLYGON((0 0,10 0,10 10,0 10,0 0))', $result[0][1]);
    }
}
