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

use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_GeogFromText DQL function tests.
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
class SpGeogFromTextTest extends OrmTestCase
{
    use PointHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEOGRAPHY_ENTITY);
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
        $newYork = $this->persistNewYorkGeography();
        $this->persistLosAngelesGeography();
        $this->persistPointA();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT g FROM LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity g WHERE g.geography = PgSQL_GeogFromText(:g)'
            // phpcs:enable
        );
        $query->setParameter('g', 'SRID=4326;POINT(-73.938611 40.664167)');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($newYork, $result[0]);
    }
}
