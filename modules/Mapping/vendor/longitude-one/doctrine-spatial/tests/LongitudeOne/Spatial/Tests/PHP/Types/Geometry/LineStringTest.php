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

namespace LongitudeOne\Spatial\Tests\PHP\Types\Geometry;

use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString;
use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * LineString object tests.
 *
 * @group php
 *
 * @internal
 * @coversDefaultClass
 */
class LineStringTest extends TestCase
{
    use LineStringHelperTrait;
    use PointHelperTrait;

    /**
     * Test LineString bad parameter.
     */
    public function testBadLineString()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid LineString Point value of type "integer"');

        new LineString([1, 2, 3, 4]);
    }

    /**
     * Test an empty line string.
     */
    public function testEmptyLineString()
    {
        $lineString = $this->createEmptyLineString();

        static::assertEmpty($lineString->getPoints());
    }

    /**
     * Test to convert line string to json.
     */
    public function testJson()
    {
        $expected = '{"type":"LineString","coordinates":[[0,0],[1,0],[1,1],[0,1],[0,0]],"srid":null}';
        $lineString = $this->createRingLineString();
        static::assertEquals($expected, $lineString->toJson());

        $expected = '{"type":"LineString","coordinates":[[0,0],[1,0],[1,1],[0,1],[0,0]],"srid":4326}';
        $lineString->setSrid(4326);
        static::assertEquals($expected, $lineString->toJson());
        static::assertEquals($expected, json_encode($lineString));
    }

    /**
     * Test to get last point.
     */
    public function testLineStringFromArraysGetLastPoint()
    {
        $expected = static::createPointE();
        $lineString = $this->createStraightLineString();
        $actual = $lineString->getPoint(-1);

        static::assertEquals($expected, $actual);
    }

    /**
     * Test to get all points of a line string.
     */
    public function testLineStringFromArraysGetPoints()
    {
        $expected = [
            static::createPointOrigin(),
            static::createPointB(),
            static::createPointE(),
        ];
        $lineString = $this->createStraightLineString();
        $actual = $lineString->getPoints();

        static::assertCount(3, $actual);
        static::assertEquals($expected, $actual);
    }

    /**
     * Test to get second point of a linestring.
     */
    public function testLineStringFromArraysGetSinglePoint()
    {
        $expected = static::createPointB();
        $lineString = $this->createStraightLineString();
        $actual = $lineString->getPoint(1);

        static::assertEquals($expected, $actual);
    }

    /**
     * Test to verify that a line is closed.
     */
    public function testLineStringFromArraysIsClosed()
    {
        $lineString = $this->createRingLineString();

        static::assertTrue($lineString->isClosed());
    }

    /**
     * Test to verify that a line is opened.
     */
    public function testLineStringFromArraysIsOpen()
    {
        $lineString = $this->createStraightLineString();

        static::assertFalse($lineString->isClosed());
    }

    /**
     * Test to convert line to string.
     */
    public function testLineStringFromArraysToString()
    {
        $expected = '0 0,1 0,1 1,0 1,0 0';
        $lineString = $this->createRingLineString();

        static::assertSame($expected, (string) $lineString);
    }

    /**
     * Test to convert line to array.
     */
    public function testLineStringFromObjectsToArray()
    {
        $expected = [
            [0, 0],
            [2, 2],
            [5, 5],
        ];
        $lineString = $this->createStraightLineString();

        static::assertCount(3, $lineString->getPoints());
        static::assertEquals($expected, $lineString->toArray());
    }
}
