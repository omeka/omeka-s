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

namespace LongitudeOne\Spatial\PHP\Types;

use CrEOF\Geo\String\Exception\RangeException;
use CrEOF\Geo\String\Exception\UnexpectedValueException;
use CrEOF\Geo\String\Parser;
use LongitudeOne\Spatial\Exception\InvalidValueException;

/**
 * Abstract point object for POINT spatial types.
 *
 * https://stackoverflow.com/questions/7309121/preferred-order-of-writing-latitude-longitude-tuples
 * https://docs.geotools.org/latest/userguide/library/referencing/order.html
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 */
abstract class AbstractPoint extends AbstractGeometry
{
    /**
     * The longitude.
     *
     * @var float
     */
    protected $x;

    /**
     * The Latitude.
     *
     * @var float
     */
    protected $y;

    /**
     * AbstractPoint constructor.
     *
     * @throws InvalidValueException when point is invalid
     */
    public function __construct()
    {
        $argv = $this->validateArguments(func_get_args());

        call_user_func_array([$this, 'construct'], $argv);
    }

    /**
     * Latitude getter.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->getY();
    }

    /**
     * Longitude getter.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->getX();
    }

    /**
     * Type getter.
     *
     * @return string Point
     */
    public function getType()
    {
        return self::POINT;
    }

    /**
     * X getter. (Longitude getter).
     *
     * @return float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Y getter. Latitude getter.
     *
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Latitude fluent setter.
     *
     * @param mixed $latitude the new latitude of point
     *
     * @throws InvalidValueException when latitude is not valid
     *
     * @return self
     */
    public function setLatitude($latitude)
    {
        return $this->setY($latitude);
    }

    /**
     * Longitude setter.
     *
     * @param mixed $longitude the new longitude
     *
     * @throws InvalidValueException when longitude is not valid
     *
     * @return self
     */
    public function setLongitude($longitude)
    {
        return $this->setX($longitude);
    }

    /**
     * X setter. (Latitude setter).
     *
     * @param mixed $x the new X
     *
     * @throws InvalidValueException when x is not valid
     *
     * @return self
     */
    public function setX($x)
    {
        $parser = new Parser($x);

        try {
            $this->x = (float) $parser->parse();
        } catch (RangeException|UnexpectedValueException $e) {
            throw new InvalidValueException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this;
    }

    /**
     * Y setter. Longitude Setter.
     *
     * @param mixed $y the new Y value
     *
     * @throws InvalidValueException when Y is invalid, not in valid range
     *
     * @return self
     */
    public function setY($y)
    {
        $parser = new Parser($y);

        try {
            $this->y = (float) $parser->parse();
        } catch (RangeException|UnexpectedValueException $e) {
            throw new InvalidValueException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this;
    }

    /**
     * Convert point into an array X, Y.
     * Latitude, longitude.
     *
     * @return array
     */
    public function toArray()
    {
        return [$this->x, $this->y];
    }

    /**
     * Abstract point constructor.
     *
     * @param int      $x    X, latitude
     * @param int      $y    Y, longitude
     * @param int|null $srid Spatial Reference System Identifier
     *
     * @throws InvalidValueException if x or y are invalid
     */
    protected function construct($x, $y, $srid = null)
    {
        $this->setX($x)
            ->setY($y)
            ->setSrid($srid)
        ;
    }

    /**
     * Validate arguments.
     *
     * @param ?array $argv list of arguments
     *
     * @throws InvalidValueException when an argument is not valid
     *
     * @return array
     */
    protected function validateArguments(array $argv = null)
    {
        $argc = count($argv);

        if (1 == $argc && is_array($argv[0])) {
            return $argv[0];
        }

        if (2 == $argc) {
            if (is_array($argv[0]) && (is_numeric($argv[1]) || null === $argv[1] || is_string($argv[1]))) {
                $argv[0][] = $argv[1];

                return $argv[0];
            }

            if ((is_numeric($argv[0]) || is_string($argv[0])) && (is_numeric($argv[1]) || is_string($argv[1]))) {
                return $argv;
            }
        }

        if (3 == $argc) {
            if ((is_numeric($argv[0]) || is_string($argv[0]))
                && (is_numeric($argv[1]) || is_string($argv[1]))
                && (is_numeric($argv[2]) || null === $argv[2] || is_string($argv[2]))
            ) {
                return $argv;
            }
        }

        array_walk($argv, function (&$value) {
            if (is_array($value)) {
                $value = 'Array';
            } else {
                $value = sprintf('"%s"', $value);
            }
        });

        throw new InvalidValueException(sprintf(
            'Invalid parameters passed to %s::%s: %s',
            get_class($this),
            '__construct',
            implode(', ', $argv)
        ));
    }
}
