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
use LongitudeOne\Spatial\PHP\Types\Geometry\MultiLineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * MultiLineString object tests.
 *
 * @group php
 *
 * @internal
 * @coversDefaultClass
 */
class MultiLineStringTest extends TestCase
{
    /**
     * Test an empty multiline string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testEmptyMultiLineString()
    {
        $multiLineString = new MultiLineString([]);

        static::assertEmpty($multiLineString->getLineStrings());
    }

    /**
     * Test to convert multiline string to json.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testJson()
    {
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $expected = '{"type":"MultiLineString","coordinates":[[[0,0],[10,0],[10,10],[0,10],[0,0]],[[0,0],[10,0],[10,10],[0,10],[0,0]]],"srid":null}';
        // phpcs:enable
        $lineStrings = [
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
        $multiLineString = new MultiLineString($lineStrings);

        static::assertEquals($expected, $multiLineString->toJson());
        static::assertEquals($expected, json_encode($multiLineString));
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $expected = '{"type":"MultiLineString","coordinates":[[[0,0],[10,0],[10,10],[0,10],[0,0]],[[0,0],[10,0],[10,10],[0,10],[0,0]]],"srid":4326}';
        // phpcs:enable
        $multiLineString->setSrid(4326);
        static::assertEquals($expected, $multiLineString->toJson());
        static::assertEquals($expected, json_encode($multiLineString));
    }

    /**
     * Test to convert a multiline string to a string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testMultiLineStringFromArraysToString()
    {
        $expected = '(0 0,10 0,10 10,0 10,0 0),(0 0,10 0,10 10,0 10,0 0)';
        $lineStrings = [
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
        $multiLineString = new MultiLineString($lineStrings);
        $result = (string) $multiLineString;

        static::assertEquals($expected, $result);
    }

    /**
     * Test to get last line from multiline string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testMultiLineStringFromObjectsGetLastLineString()
    {
        $firstLineString = new LineString(
            [
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]
        );
        $lastLineString = new LineString(
            [
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5),
            ]
        );
        $polygon = new MultiLineString([$firstLineString, $lastLineString]);

        static::assertEquals($lastLineString, $polygon->getLineString(-1));
    }

    /**
     * Test to get first line from multiline string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testMultiLineStringFromObjectsGetSingleLineString()
    {
        $firstLineString = new LineString(
            [
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]
        );
        $lastLineString = new LineString(
            [
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5),
            ]
        );
        $multiLineString = new MultiLineString([$firstLineString, $lastLineString]);

        static::assertEquals($firstLineString, $multiLineString->getLineString(0));
    }

    /**
     * Test to create multiline string from line string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testMultiLineStringFromObjectsToArray()
    {
        $expected = [
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
        $lineStrings = [
            new LineString(
                [
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]
            ),
            new LineString(
                [
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]
            ),
        ];

        $multiLineString = new MultiLineString($lineStrings);

        static::assertEquals($expected, $multiLineString->toArray());
    }

    /**
     * Test a solid multiline string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testSolidMultiLineStringAddRings()
    {
        $expected = [
            new LineString(
                [
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]
            ),
            new LineString(
                [
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]
            ),
        ];
        $rings = [
            [
                [0, 0],
                [10, 0],
                [10, 10],
                [0, 10],
                [0, 0],
            ],
        ];

        $multiLineString = new MultiLineString($rings);

        $multiLineString->addLineString(
            [
                [0, 0],
                [10, 0],
                [10, 10],
                [0, 10],
                [0, 0],
            ]
        );

        static::assertEquals($expected, $multiLineString->getLineStrings());
    }

    /**
     * Test a solid multiline string.
     *
     * @throws InvalidValueException This should not happen because of selected value
     */
    public function testSolidMultiLineStringFromArraysGetRings()
    {
        $expected = [
            new LineString(
                [
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]
            ),
            new LineString(
                [
                    new Point(0, 0),
                    new Point(10, 0),
                    new Point(10, 10),
                    new Point(0, 10),
                    new Point(0, 0),
                ]
            ),
        ];
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

        $multiLineString = new MultiLineString($rings);

        static::assertEquals($expected, $multiLineString->getLineStrings());
    }
}
