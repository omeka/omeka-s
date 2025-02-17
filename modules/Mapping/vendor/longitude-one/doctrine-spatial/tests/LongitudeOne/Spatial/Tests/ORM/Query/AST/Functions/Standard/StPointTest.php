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
 * ST_Point DQL function tests.
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
class StPointTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POINT_ENTITY);
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
        $this->persistToursLambert93(false);
        $pointO = $this->persistPointO();
        $this->persistPointA();
        $this->persistPointB();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity p WHERE ST_EQUALS(p.point, ST_Point(:x, :y)) = true'
            // phpcs:enable
        );
        $query->setParameter('x', 0, 'integer');
        $query->setParameter('y', 0, 'integer');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($pointO, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testSelectWithSrid()
    {
        $tours = $this->persistToursLambert93(true);
        $this->persistGeometryParisLambert93(true);

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity p WHERE ST_EQUALS(p.point, ST_SetSRID(ST_Point(:x, :y), :srid)) = true'
            // phpcs:enable
        );

        $query->setParameter('x', 525375.21);
        $query->setParameter('y', 6701871.83);
        $query->setParameter('srid', 2154);

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($tours, $result[0]);
    }
}
