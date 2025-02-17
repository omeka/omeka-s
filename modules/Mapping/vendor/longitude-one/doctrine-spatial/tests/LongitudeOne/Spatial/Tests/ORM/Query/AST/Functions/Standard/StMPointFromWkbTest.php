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

use LongitudeOne\Spatial\Tests\Helper\GeometryHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_MPointFromWkb DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StMPointFromWkbTest extends OrmTestCase
{
    use GeometryHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEOMETRY_ENTITY);
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
        $this->persistGeometryO(); // Unused fake point
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT t, ST_AsText(ST_MPointFromWkb(:wkb)) FROM LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity t'
        );
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $query->setParameter('wkb', hex2bin('0104000000030000000101000000000000000000000000000000000000000101000000000000000000F03F000000000000F03F010100000000000000000000400000000000000040'), 'blob');
        // phpcs:enable

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertMatchesRegularExpression('|^MULTIPOINT\(|', $result[0][1]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectWithSrid()
    {
        $this->persistGeometryO(); // Unused fake point
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT t, ST_SRID(ST_MPointFromWkb(:wkb, :srid)) FROM LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity t'
        );
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $query->setParameter('wkb', hex2bin('0104000000030000000101000000000000000000000000000000000000000101000000000000000000F03F000000000000F03F010100000000000000000000400000000000000040'), 'blob');
        // phpcs:enable
        $query->setParameter('srid', 2154);

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals(2154, $result[0][1]);
    }
}
