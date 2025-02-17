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
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\PHP\Types\Geometry\Polygon;
use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Polygon object tests.
 *
 * @group php
 *
 * @internal
 * @coversDefaultClass
 */
class PolygonTest extends TestCase
{
    use LineStringHelperTrait;
    use PolygonHelperTrait;

    /**
     * Test to get last ring.
     */
    public function testAddPolygonToPolygon()
    {
        static::expectExceptionMessage('You cannot add a Polygon to another one. Use a Multipolygon.');
        static::expectException(InvalidValueException::class);
        $ringA = $this->createBigPolygon();
        $polygon = $this->createEmptyPolygon();
        $polygon->addRing($ringA);
    }

    /**
     * Test an empty polygon.
     */
    public function testEmptyPolygon(): void
    {
        $polygon = $this->createEmptyPolygon();

        static::assertEmpty($polygon->getRings());
    }

    /**
     * Test to export json.
     */
    public function testJson(): void
    {
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $expected = '{"type":"Polygon","coordinates":[[[0,0],[10,0],[10,10],[0,10],[0,0]],[[5,5],[7,5],[7,7],[5,7],[5,5]]],"srid":null}';
        // phpcs:enable
        $polygon = $this->createHoleyPolygon();
        static::assertEquals($expected, $polygon->toJson());
        static::assertEquals($expected, json_encode($polygon));

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $expected = '{"type":"Polygon","coordinates":[[[0,0],[10,0],[10,10],[0,10],[0,0]],[[5,5],[7,5],[7,7],[5,7],[5,5]]],"srid":4326}';
        // phpcs:enable
        $polygon->setSrid(4326);
        static::assertEquals($expected, $polygon->toJson());
        static::assertEquals($expected, json_encode($polygon));
    }

    /**
     * Test Polygon with open ring.
     */
    public function testOpenPolygonRing()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid polygon, ring "(0 0,10 0,10 10,0 10)" is not closed');

        $rings = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
            ]),
        ];

        new Polygon($rings);
    }

    /**
     * Test to get last ring.
     */
    public function testRingPolygonFromObjectsGetLastRing()
    {
        $ringA = $this->createRingLineString();
        $ringB = $this->createNodeLineString();
        $polygon = $this->createEmptyPolygon();
        try {
            $polygon->addRing($ringA);
            $polygon->addRing($ringB);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to add ring to polygon: %s', $e->getMessage()));
        }

        static::assertEquals($ringB, $polygon->getRing(-1));
    }

    /**
     * Test to get the first ring.
     */
    public function testRingPolygonFromObjectsGetSingleRing()
    {
        $ringA = $this->createRingLineString();
        $ringB = $this->createNodeLineString();
        $polygon = $this->createEmptyPolygon();
        try {
            $polygon->addRing($ringA);
            $polygon->addRing($ringB);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to add ring to polygon: %s', $e->getMessage()));
        }

        static::assertEquals($ringA, $polygon->getRing(0));
    }

    /**
     * Test a solid polygon from array add rings.
     */
    public function testSolidPolygonFromArrayAddRings()
    {
        $expected = [$this->createRingLineString(), $this->createNodeLineString()];
        $ring = [
            [
                [0, 0],
                [1, 0],
                [1, 1],
                [0, 1],
                [0, 0],
            ],
        ];

        try {
            $polygon = new Polygon($ring);

            $polygon->addRing(
                [
                    [0, 0],
                    [1, 0],
                    [0, 1],
                    [1, 1],
                    [0, 0],
                ]
            );

            static::assertEquals($expected, $polygon->getRings());
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to add ring to polygon: %s', $e->getMessage()));
        }
    }

    /**
     * Test a solid polygon from an array of points.
     */
    public function testSolidPolygonFromArrayOfPoints()
    {
        $expected = [
            [
                [0, 0],
                [1, 0],
                [1, 1],
                [0, 1],
                [0, 0],
            ],
        ];
        $rings = $this->createRingLineString();

        try {
            $polygon = new Polygon([$rings]);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create polygon from ring linestring: %s', $e->getMessage()));
        }

        static::assertEquals($expected, $polygon->toArray());
    }

    /**
     * Test a solid polygon from an array of rings.
     */
    public function testSolidPolygonFromArraysOfRings()
    {
        $expected = [$this->createRingLineString()];
        $rings = [
            [
                [0, 0],
                [1, 0],
                [1, 1],
                [0, 1],
                [0, 0],
            ],
        ];

        try {
            $polygon = new Polygon($rings);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create polygon from ring linestring: %s', $e->getMessage()));
        }

        static::assertEquals($expected, $polygon->getRings());
    }

    /**
     * Test a solid polygon from arrays to string.
     */
    public function testSolidPolygonFromArraysToString()
    {
        $expected = '(0 0,10 0,10 10,0 10,0 0),(0 0,10 0,10 10,0 10,0 0)';
        $rings = [
            [
                [0, 0],
                [10, 0],
                [10, 10],
                [0, 10],
                [0, 0],
            ],
            [
                [0, 0],
                [10, 0],
                [10, 10],
                [0, 10],
                [0, 0],
            ],
        ];
        try {
            $polygon = new Polygon($rings);
            $result = (string) $polygon;

            static::assertSame($expected, $result);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create polygon from array: %s', $e->getMessage()));
        }
    }

    /**
     * Test solid polygon from objects to array.
     */
    public function testSolidPolygonFromObjectsToArray()
    {
        $expected = [
            [
                [0, 0],
                [1, 0],
                [1, 1],
                [0, 1],
                [0, 0],
            ],
        ];
        $rings = [$this->createRingLineString()];

        try {
            $polygon = new Polygon($rings);
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create polygon from ring linestring: %s', $e->getMessage()));
        }

        static::assertEquals($expected, $polygon->toArray());
    }
}
