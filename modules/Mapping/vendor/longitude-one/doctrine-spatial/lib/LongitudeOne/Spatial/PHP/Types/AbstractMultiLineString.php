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

use LongitudeOne\Spatial\Exception\InvalidValueException;

/**
 * Abstract MultiLineString object for MULTILINESTRING spatial types.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 */
abstract class AbstractMultiLineString extends AbstractGeometry
{
    /**
     * Array of line strings.
     *
     * @var array[]
     */
    protected $lineStrings = [];

    /**
     * AbstractMultiLineString constructor.
     *
     * @param AbstractLineString[]|array[] $rings array of linestring
     * @param int|null                     $srid  Spatial Reference System Identifier
     *
     * @throws InvalidValueException when rings contains an invalid linestring
     */
    public function __construct(array $rings, $srid = null)
    {
        $this->setLineStrings($rings)
            ->setSrid($srid)
        ;
    }

    /**
     * Add a linestring to geometry.
     *
     * @param AbstractLineString|array[] $lineString the line string to add to Geometry
     *
     * @throws InvalidValueException when linestring is not valid
     *
     * @return self
     */
    public function addLineString($lineString)
    {
        $this->lineStrings[] = $this->validateLineStringValue($lineString);

        return $this;
    }

    /**
     * Return linestring at specified offset.
     *
     * @param int $index offset of line string to return. Use -1 to get last linestring.
     *
     * @return AbstractLineString
     */
    public function getLineString($index)
    {
        if (-1 == $index) {
            $index = count($this->lineStrings) - 1;
        }

        $lineStringClass = $this->getNamespace().'\LineString';

        return new $lineStringClass($this->lineStrings[$index], $this->srid);
    }

    /**
     * Line strings getter.
     *
     * @return AbstractLineString[]
     */
    public function getLineStrings()
    {
        $lineStrings = [];

        for ($i = 0; $i < count($this->lineStrings); ++$i) {
            $lineStrings[] = $this->getLineString($i);
        }

        return $lineStrings;
    }

    /**
     * Type getter.
     *
     * @return string MultiLineString
     */
    public function getType()
    {
        return self::MULTILINESTRING;
    }

    /**
     * LineStrings fluent setter.
     *
     * @param AbstractLineString[] $lineStrings array of LineString
     *
     * @throws InvalidValueException when a linestring is not valid
     *
     * @return self
     */
    public function setLineStrings(array $lineStrings)
    {
        $this->lineStrings = $this->validateMultiLineStringValue($lineStrings);

        return $this;
    }

    /**
     * Implements abstract method to convert line strings into an array.
     *
     * @return array[]
     */
    public function toArray()
    {
        return $this->lineStrings;
    }
}
