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

namespace LongitudeOne\Spatial\PHP\Types\Geography;

use CrEOF\Geo\String\Exception\RangeException;
use CrEOF\Geo\String\Exception\UnexpectedValueException;
use CrEOF\Geo\String\Parser;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\AbstractPoint;

/**
 * Point object for POINT geography type.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 */
class Point extends AbstractPoint implements GeographyInterface
{
    /**
     * X setter.
     *
     * @param mixed $x X coordinate
     *
     * @throws InvalidValueException when y is not in range of accepted value, or is totally invalid
     *
     * @return self
     */
    public function setX($x)
    {
        $parser = new Parser($x);

        try {
            $x = (float) $parser->parse();
        } catch (RangeException|UnexpectedValueException $e) {
            throw new InvalidValueException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        if ($x < -180 || $x > 180) {
            throw new InvalidValueException(sprintf('Invalid longitude value "%s", must be in range -180 to 180.', $x));
        }

        $this->x = $x;

        return $this;
    }

    /**
     * Y setter.
     *
     * @param mixed $y the Y coordinate
     *
     * @throws InvalidValueException when y is not in range of accepted value, or is totally invalid
     *
     * @return self
     */
    public function setY($y)
    {
        $parser = new Parser($y);

        try {
            $y = (float) $parser->parse();
        } catch (RangeException|UnexpectedValueException $e) {
            throw new InvalidValueException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        if ($y < -90 || $y > 90) {
            throw new InvalidValueException(sprintf('Invalid latitude value "%s", must be in range -90 to 90.', $y));
        }

        $this->y = $y;

        return $this;
    }
}
