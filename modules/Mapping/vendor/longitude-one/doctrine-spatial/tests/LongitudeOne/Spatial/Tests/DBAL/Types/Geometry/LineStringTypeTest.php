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

namespace LongitudeOne\Spatial\Tests\DBAL\Types\Geometry;

use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity;
use LongitudeOne\Spatial\Tests\Helper\PersistHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * Doctrine LineStringType tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group geometry
 *
 * @internal
 * @coversDefaultClass \LongitudeOne\Spatial\DBAL\Types\Geometry\LineStringType
 */
class LineStringTypeTest extends OrmTestCase
{
    use PersistHelperTrait;

    /**
     * Setup the test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
        parent::setUp();
    }

    /**
     * Test to store and find a line string in table.
     *
     * @throws InvalidValueException when geometries are not valid
     */
    public function testFindByLineString()
    {
        $lineString = new LineString(
            [
                new Point(0, 0),
                new Point(1, 1),
                new Point(2, 2),
            ]
        );
        $entity = new LineStringEntity();

        $entity->setLineString($lineString);
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store and find it by id.
     *
     * @throws InvalidValueException when geometries are not valid
     */
    public function testLineString()
    {
        $lineString = new LineString(
            [
                new Point(0, 0),
                new Point(1, 1),
                new Point(2, 2),
            ]
        );
        $entity = new LineStringEntity();

        $entity->setLineString($lineString);
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    /**
     * Test to store a null line string, then to find it with its id.
     */
    public function testNullLineStringType()
    {
        $entity = new LineStringEntity();
        static::assertIsRetrievableById($this->getEntityManager(), $entity);
    }

    //TODO test to find all null linestring
}
