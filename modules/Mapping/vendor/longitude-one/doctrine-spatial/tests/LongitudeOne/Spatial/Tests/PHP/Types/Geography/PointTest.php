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

namespace LongitudeOne\Spatial\Tests\PHP\Types\Geography;

use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
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
     * Test bad numeric parameters - latitude greater than 90.
     *
     * @throws InvalidValueException it should happen
     */
    public function testBadNumericGreaterThanLatitude()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid latitude value "190", must be in range -90 to 90.');

        new Point(55, 190);
    }

    /**
     * Test bad numeric parameters - longitude greater than 180.
     *
     * @throws InvalidValueException it should happen
     */
    public function testBadNumericGreaterThanLongitude()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid longitude value "180.134", must be in range -180 to 180.');

        new Point(180.134, 54);
    }

    /**
     * Test bad numeric parameters - latitude less than -90.
     *
     * @throws InvalidValueException it should happen
     */
    public function testBadNumericLessThanLatitude()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid latitude value "-90.00001", must be in range -90 to 90.');

        new Point(55, -90.00001);
    }

    /**
     * Test bad numeric parameters - longitude less than -180.
     *
     * @throws InvalidValueException it should happen
     */
    public function testBadNumericLessThanLongitude()
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid longitude value "-230", must be in range -180 to 180.');

        new Point(-230, 54);
    }

    /**
     * Test getType method.
     *
     * @throws InvalidValueException it should NOT happen
     */
    public function testGetType()
    {
        $point = new Point(10, 10);
        $result = $point->getType();

        static::assertEquals('Point', $result);
    }

    /**
     * Test a valid numeric point.
     *
     * @throws InvalidValueException it should NOT happen
     */
    public function testGoodNumericPoint()
    {
        $point = new Point(-73.7562317, 42.6525793);

        static::assertEquals(42.6525793, $point->getLatitude());
        static::assertEquals(-73.7562317, $point->getLongitude());
    }

    /**
     * Test valid string points.
     */
    public function testGoodStringPoints()
    {
        $point = new Point('79:56:55W', '40:26:46N');
        $expected = '{"type":"Point","coordinates":[-79.9486111111111,40.44611111111111],"srid":null}';

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());
        static::assertEquals($expected, $point->toJson());
        static::assertEquals($expected, json_encode($point));

        $point = new Point('79°56\'55"W', '40°26\'46"N');
        $point->setSrid(4326);
        $expected = '{"type":"Point","coordinates":[-79.9486111111111,40.44611111111111],"srid":4326}';

        static::assertEquals(40.446111111111, $point->getLatitude());
        static::assertEquals(-79.948611111111, $point->getLongitude());
        static::assertEquals($expected, $point->toJson());
        static::assertEquals($expected, json_encode($point));

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
     * Test a point created with an array and converts to string.
     *
     * @throws InvalidValueException it should NOT happen
     */
    public function testPointFromArrayToString()
    {
        $expected = '5 5';
        $point = new Point([5, 5]);

        static::assertEquals($expected, (string) $point);
    }

    /**
     * Test error when point created with too many arguments.
     *
     * @throws InvalidValueException it should happen
     */
    public function testPointTooManyArguments()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geography\\Point::__construct: "5", "5", "5", "5"');
        // phpcs:enable

        new Point(5, 5, 5, 5);
    }

    /**
     * Test a point with SRID.
     *
     * @throws InvalidValueException it should not happen
     */
    public function testPointWithSrid()
    {
        $point = new Point(10, 10, 4326);
        $result = $point->getSrid();

        static::assertEquals(4326, $result);

        //Lambert
        $point = new Point(10, 10, 2154);
        $result = $point->getSrid();

        static::assertEquals(2154, $result);
    }

    /**
     * Test error when point is created with wrong arguments.
     *
     * @throws InvalidValueException it should happen
     */
    public function testPointWrongArgumentTypes()
    {
        $this->expectException(InvalidValueException::class);
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->expectExceptionMessage('Invalid parameters passed to LongitudeOne\\Spatial\\PHP\\Types\\Geography\\Point::__construct: Array, Array, "1234"');
        // phpcs:enable

        new Point([], [], '1234');
    }

    /**
     * Test to convert point to array.
     *
     * @throws InvalidValueException it should happen
     */
    public function testToArray()
    {
        $expected = [10, 10];
        $point = new Point(10, 10);
        $result = $point->toArray();

        static::assertEquals($expected, $result);
    }
}
