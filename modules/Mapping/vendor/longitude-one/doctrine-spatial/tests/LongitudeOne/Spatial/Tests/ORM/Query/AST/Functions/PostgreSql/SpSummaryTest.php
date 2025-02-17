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

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geography\LineString as GeographyLineString;
use LongitudeOne\Spatial\PHP\Types\Geography\Point as GeographyPoint;
use LongitudeOne\Spatial\PHP\Types\Geography\Polygon as GeographyPolygon;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString as GeometryLineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point as GeometryPoint;
use LongitudeOne\Spatial\PHP\Types\Geometry\Polygon as GeometryPolygon;
use LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity;
use LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * SP_Summary DQL function tests.
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
class SpSummaryTest extends OrmTestCase
{
    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEOMETRY_ENTITY);
        $this->usesEntity(self::GEOGRAPHY_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select with a geography.
     *
     * @throws ORMException            when cache is not set
     * @throws MappingException        when mapping
     * @throws OptimisticLockException when clear fails
     * @throws InvalidValueException   when geometries are not valid
     *
     * @group geography
     */
    public function testSelectStSummaryGeography()
    {
        $point = new GeographyEntity();
        $point->setGeography(new GeographyPoint(5, 5));
        $this->getEntityManager()->persist($point);

        $linestring = new GeographyEntity();
        $linestring->setGeography(new GeographyLineString([
            [1, 1],
            [2, 2],
            [3, 3],
        ]));
        $this->getEntityManager()->persist($linestring);

        $polygon = new GeographyEntity();
        $polygon->setGeography(new GeographyPolygon([[
            [0, 0],
            [10, 0],
            [10, 10],
            [0, 10],
            [0, 0],
        ]]));
        $this->getEntityManager()->persist($polygon);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT g, PgSql_Summary(g.geography) FROM LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity g'
        );
        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($point, $result[0][0]);
        static::assertMatchesRegularExpression('/^Point\[.*G.*]/', $result[0][1]);
        static::assertEquals($linestring, $result[1][0]);
        static::assertMatchesRegularExpression('/^LineString\[.*G.*]/', $result[1][1]);
        static::assertEquals($polygon, $result[2][0]);
        static::assertMatchesRegularExpression('/^Polygon\[.*G.*]/', $result[2][1]);
    }

    /**
     * Test a DQL containing function to test in the select with a geometry.
     *
     * @throws ORMException            when cache is not set
     * @throws MappingException        when mapping
     * @throws OptimisticLockException when clear fails
     * @throws InvalidValueException   when geometries are not valid
     *
     * @group geometry
     */
    public function testSelectStSummaryGeometry()
    {
        $point = new GeometryEntity();
        $point->setGeometry(new GeometryPoint(5, 5));
        $this->getEntityManager()->persist($point);

        $linestring = new GeometryEntity();
        $linestring->setGeometry(new GeometryLineString([
            [1, 1],
            [2, 2],
            [3, 3],
        ]));
        $this->getEntityManager()->persist($linestring);

        $polygon = new GeometryEntity();
        $polygon->setGeometry(new GeometryPolygon([[
            [0, 0],
            [10, 0],
            [10, 10],
            [0, 10],
            [0, 0],
        ]]));
        $this->getEntityManager()->persist($polygon);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT g, PgSql_Summary(g.geometry) FROM LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity g'
        );
        $result = $query->getResult();

        static::assertCount(3, $result);
        static::assertEquals($point, $result[0][0]);
        static::assertMatchesRegularExpression('/^Point\[[^G]*]/', $result[0][1]);
        static::assertEquals($linestring, $result[1][0]);
        static::assertMatchesRegularExpression('/^LineString\[[^G]*]/', $result[1][1]);
        static::assertEquals($polygon, $result[2][0]);
        static::assertMatchesRegularExpression('/^Polygon\[[^G]*]/', $result[2][1]);
    }
}
