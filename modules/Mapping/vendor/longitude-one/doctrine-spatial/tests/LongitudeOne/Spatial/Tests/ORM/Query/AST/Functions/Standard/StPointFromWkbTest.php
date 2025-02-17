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

use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_PointFromWKB DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StPointFromWkbTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

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
            'SELECT t, ST_AsText(ST_PointFromWkb(:wkb)) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity t'
            // phpcs:enable
        );
        $query->setParameter('wkb', hex2bin('0101000000000000000000F03F000000000000F0BF'), 'blob');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals('POINT(1 -1)', $result[0][1]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectWithSrid()
    {
        $this->persistNewYorkGeometry(); // Unused fake point
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT t, ST_SRID(ST_PointFromWkb(:wkb, :srid)) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity t'
            // phpcs:enable
        );
        $query->setParameter('wkb', hex2bin('0101000000000000000000F03F000000000000F0BF'), 'blob');
        $query->setParameter('srid', 2154);

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals(2154, $result[0][1]);
    }
}
