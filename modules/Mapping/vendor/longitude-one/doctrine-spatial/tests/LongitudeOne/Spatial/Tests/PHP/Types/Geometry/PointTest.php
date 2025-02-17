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
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Point object tests.
 *
 * @group php
 *
 * @internal
 * @coversDefaultClass
 */
class PointTest extends TestCase
{
    use PointHelperTrait;

    /**
     * Test bad string parameters - latitude degrees greater that 90.
     */
    public function testBadLatitudeDegrees()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('[Range Error] Error: Degrees out of range -90 to 90 in value "92:26:46N"');

        new Point('79:56:55W', '92:26:46N');
    }

    /**
     * Test bad string parameters - invalid latitude direction.
     */
    public function testBadLatitudeDirection()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('[Syntax Error] line 0, col 8: Error: Expected CrEOF\\Geo\\String\\Lexer::T_INTEGER or CrEOF\\Geo\\String\\Lexer::T_FLOAT, got "Q" in value "84:26:46Q"');
        // phpcs:enable

        new Point('100:56:55W', '84:26:46Q');
    }

    /**
     * Test bad string parameters - latitude minutes greater than 59.
     */
    public function testBadLatitudeMinutes()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('[Range Error] Error: Minutes greater than 60 in value "84:64:46N"');

        new Point('108:42:55W', '84:64:46N');
    }

    /**
     * Test bad string parameters - latitude seconds greater than 59.
     */
    public function testBadLatitudeSeconds()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('[Range Error] Error: Seconds greater than 60 in value "84:23:75N"');

        new Point('108:42:55W', '84:23:75N');
    }

    /**
     * Test bad string parameters - longitude degrees greater than 180.
     */
    public function testBadLongitudeDegrees()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('[Range Error] Error: Degrees out of range -180 to 180 in value "190:56:55W"');

        new Point('190:56:55W', '84:26:46N');
    }

    /**
     * Test bad string parameters - invalid longitude direction.
     */
    public function testBadLongitudeDirection()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('[Syntax Error] line 0, col 9: Error: Expected CrEOF\\Geo\\String\\Lexer::T_INTEGER or CrEOF\\Geo\\String\\Lexer::T_FLOAT, got "P" in value "100:56:55P"');
        // phpcs:enable

        new Point('100:56:55P', '84:26:46N');
    }

    /**
     * Test bad string parameters - longitude minutes greater than 59.
     */
    public function testBadLongitudeMinutes()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('[Range Error] Error: Minutes greater than 60 in value "108:62:55W"');

        new Point('108:62:55W', '84:26:46N');
    }

    /**
     * Test bad string parameters - longitude seconds greater than 59.
     */
    public function testBadLongitudeSeconds()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('[Range Error] Error: Seconds greater than 60 in value "108:53:94W"');

        new Point('108:53:94W', '84:26:46N');
    }

    /**
     * Test getType method.
     */
    public function testGetType()
    {
        $point = static::createPointOrigin();
        $result = $point->getType();

        static::assertEquals('Point', $result);
    }

    /**
     * Test a valid numeric point.
     */
    public function testGoodNumericPoint()
    {
        $point = $this->createLosAngelesGeometry();

        static::assertEquals(34.0522, $point->getLatitude());
        static::assertEquals(-118.2430, $point->getLongitude());

        try {
            $point
                ->setLatitude(32.782778)
                ->setLongitude(-96.803889)
            ;
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to update geometry point: %s', $e->getMessage()));
        }

        static::assertEquals(32.782778, $point->getLatitude());
        static::assertEquals(-96.803889, $point->getLongitude());
    }

    /**
     * Test valid string points.
     */
    public function testGoodStringPoints()
    {
        $point = new Point('79:56:55W', '40:26:46N');

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());

        $point = new Point('79°56\'55"W', '40°26\'46"N');

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());

        $point = new Point('79° 56\' 55" W', '40° 26\' 46" N');

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());

        $point = new Point('79°56′55″W', '40°26′46″N');

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());

        $point = new Point('79° 56′ 55″ W', '40° 26′ 46″ N');

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());

        $point = new Point('79:56:55.832W', '40:26:46.543N');

        static::assertEquals(40.446261944444, $point->getLatitude());
        static::assertEquals(-79.948842222222, $point->getLongitude());

        $point = new Point('112:4:0W', '33:27:0N');

        static::assertEquals(33.45, $point->getLatitude());
        static::assertEquals(-112.06666666667, $point->getLongitude());
    }

    /**
     * Test to convert point to json.
     */
    public function testJson()
    {
        $expected = '{"type":"Point","coordinates":[5,5],"srid":null}';
        $point = static::createPointE();

        static::assertEquals($expected, $point->toJson());
        static::assertEquals($expected, json_encode($point));

        $point->setSrid(4326);
        $expected = '{"type":"Point","coordinates":[5,5],"srid":4326}';
        static::assertEquals($expected, $point->toJson());
        static::assertEquals($expected, json_encode($point));
    }

    /**
     * Test bad string parameters - No parameters.
     */
    public function testMissingArguments()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\Point::__construct:');
        // phpcs:enable

        new Point();
    }

    /**
     * Test a point created with an array.
     */
    public function testPointFromArrayToString()
    {
        $expected = '5 5';
        $point = static::createPointE();

        static::assertSame($expected, (string) $point);
    }

    /**
     * Test error when point is created with too many arguments.
     */
    public function testPointTooManyArguments()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\Point::__construct: "5", "5", "5", "5"');
        // phpcs:enable

        new Point(5, 5, 5, 5);
    }

    /**
     * Test point with srid.
     */
    public function testPointWithSrid()
    {
        $point = static::createPointWithSrid(2154);
        $result = $point->getSrid();

        static::assertSame(2154, $result);
    }

    /**
     * Test error when point was created with wrong arguments type.
     */
    public function testPointWrongArgumentTypes()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\Point::__construct: Array, Array, "1234"');
        // phpcs:enable

        new Point([], [], '1234');
    }

    /**
     * Test to convert a point to an array.
     */
    public function testToArray()
    {
        $expected = [0.0, 0.0];
        $point = static::createPointOrigin();
        $result = $point->toArray();

        static::assertSame($expected, $result);
    }

    /**
     * Test bad string parameters - Two invalid parameters.
     */
    public function testTwoInvalidArguments()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\Point::__construct: "", ""');
        // phpcs:enable

        new Point(null, null);
    }

    /**
     * Test bad string parameters - More than 3 parameters.
     */
    public function testUnusedArguments()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\Point::__construct: "1", "2", "3", "4", "", "5"');
        // phpcs:enable

        new Point(1, 2, 3, 4, null, 5);
    }
}
