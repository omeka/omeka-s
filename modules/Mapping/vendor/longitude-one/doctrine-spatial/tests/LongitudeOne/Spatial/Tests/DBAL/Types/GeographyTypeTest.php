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

namespace LongitudeOne\Spatial\Tests\DBAL\Types;

use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geography\LineString;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use LongitudeOne\Spatial\PHP\Types\Geography\Polygon;
use LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity;
use LongitudeOne\Spatial\Tests\Helper\PersistHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * Doctrine GeographyType tests.
 *
 * @group geography
 *
 * @internal
 * @coversDefaultClass \LongitudeOne\Spatial\DBAL\Types\GeographyType
 */
class GeographyTypeTest extends OrmTestCase
{
    use PersistHelperTrait;

    /**
     * Setup the geography type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEOGRAPHY_ENTITY);

        parent::setUp();
    }

    /**
     * Test to store and retrieve a geography composed by a linestring.
     *
     * @throws InvalidValueException when geometries are not valid
     */
    public function testLineStringGeography()
    {
        $entity = new GeographyEntity();

        $entity->setGeography(new LineString([
            new Point(0, 0),
            new Point(1, 1),
        ]));
        $this->assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store and retrieve a null geography.
     */
    public function testNullGeography()
    {
        $entity = new GeographyEntity();
        $this->assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store and retrieve a geography composed by a single point.
     *
     * @throws InvalidValueException when geometries are not valid
     */
    public function testPointGeography()
    {
        $entity = new GeographyEntity();

        $entity->setGeography(new Point(1, 1));
        $this->assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store and retrieve a geography composed by a polygon.
     *
     * @throws InvalidValueException when geometries are not valid
     */
    public function testPolygonGeography()
    {
        $entity = new GeographyEntity();

        $rings = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
        ];

        $entity->setGeography(new Polygon($rings));
        $this->assertIsRetrievableById($this->getEntityManager(), $entity);
    }
}
