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

namespace LongitudeOne\Spatial\Tests\ORM\Query;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use LongitudeOne\Spatial\Tests\Helper\GeometryHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * DQL type wrapping tests.
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
class WrappingTest extends OrmTestCase
{
    use GeometryHelperTrait;
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->usesEntity(self::GEOMETRY_ENTITY);
        $this->usesType('point');
        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @group geometry
     */
    public function testTypeWrappingSelect()
    {
        $this->persistBigPolygon();
        $smallPolygon = $this->createSmallPolygon();

        $dql = 'SELECT p, ST_Contains(p.polygon, :geometry) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p';

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('geometry', $smallPolygon, 'point');
        $query->processParameterValue('geometry');

        $result = $query->getSQL();

        try {
            $parameter = Type::getType('point')->convertToDatabaseValueSql('?', $this->getPlatform());
        } catch (Exception $e) {
            static::fail(sprintf('Unable to get type point: %s', $e->getMessage()));
        }

        $regex = preg_quote(sprintf('/.polygon, %s)/', $parameter));

        static::assertMatchesRegularExpression($regex, $result);
    }

    /**
     * @group geometry
     */
    public function testTypeWrappingWhere()
    {
        $this->persistGeometryE();

        $query = $this->getEntityManager()->createQuery(
            'SELECT g FROM LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity g WHERE g.geometry = :geometry'
        );

        $query->setParameter('geometry', $this->createGeometryPoint('E', 5, 5), 'point');
        $query->processParameterValue('geometry');

        $result = $query->getSQL();
        try {
            $parameter = Type::getType('point')->convertToDatabaseValueSql('?', $this->getPlatform());
        } catch (Exception $e) {
            static::fail(sprintf('Unable to get type point: %s', $e->getMessage()));
        }

        $regex = preg_quote(sprintf('/geometry = %s/', $parameter));

        static::assertMatchesRegularExpression($regex, $result);
    }
}
