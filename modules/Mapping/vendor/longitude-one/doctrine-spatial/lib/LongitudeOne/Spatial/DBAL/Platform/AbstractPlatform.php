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

namespace LongitudeOne\Spatial\DBAL\Platform;

use CrEOF\Geo\WKB\Parser as BinaryParser;
use CrEOF\Geo\WKT\Parser as StringParser;
use LongitudeOne\Spatial\DBAL\Types\AbstractSpatialType;
use LongitudeOne\Spatial\DBAL\Types\GeographyType;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;

/**
 * Abstract spatial platform.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre-tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 */
abstract class AbstractPlatform implements PlatformInterface
{
    /**
     * Convert binary data to a php value.
     *
     * @param AbstractSpatialType $type    The abstract spatial type
     * @param string              $sqlExpr the SQL expression
     *
     * @throws InvalidValueException when the provided type is not supported
     *
     * @return GeometryInterface
     */
    public function convertBinaryToPhpValue(AbstractSpatialType $type, $sqlExpr)
    {
        $parser = new BinaryParser($sqlExpr);

        return $this->newObjectFromValue($type, $parser->parse());
    }

    /**
     * Convert string data to a php value.
     *
     * @param AbstractSpatialType $type    The abstract spatial type
     * @param string              $sqlExpr the SQL expression
     *
     * @throws InvalidValueException when the provided type is not supported
     *
     * @return GeometryInterface
     */
    public function convertStringToPhpValue(AbstractSpatialType $type, $sqlExpr)
    {
        $parser = new StringParser($sqlExpr);

        return $this->newObjectFromValue($type, $parser->parse());
    }

    // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed

    /**
     * Convert binary data to a php value.
     *
     * @param AbstractSpatialType $type  The spatial type
     * @param GeometryInterface   $value The geometry object
     *
     * @return string
     */
    public function convertToDatabaseValue(AbstractSpatialType $type, GeometryInterface $value)
    {
        //the unused variable $type is used by overriding method
        return sprintf('%s(%s)', mb_strtoupper($value->getType()), $value);
    }

    // phpcs:enable

    /**
     * Get an array of database types that map to this Doctrine type.
     *
     * @param AbstractSpatialType $type the spatial type
     *
     * @return string[]
     */
    public function getMappedDatabaseTypes(AbstractSpatialType $type)
    {
        $sqlType = mb_strtolower($type->getSQLType());

        if ($type instanceof GeographyType && 'geography' !== $sqlType) {
            $sqlType = sprintf('geography(%s)', $sqlType);
        }

        return [$sqlType];
    }

    /**
     * Create spatial object from parsed value.
     *
     * @param AbstractSpatialType $type  The type spatial type
     * @param array               $value The value of the spatial object
     *
     * @throws InvalidValueException when the provided type is not supported
     *
     * @return GeometryInterface
     */
    private function newObjectFromValue(AbstractSpatialType $type, $value)
    {
        $typeFamily = $type->getTypeFamily();
        $typeName = mb_strtoupper($value['type']);

        $constName = sprintf('%s::%s', GeometryInterface::class, $typeName);

        if (!defined($constName)) {
            throw new InvalidValueException(sprintf('Unsupported %s type "%s".', $typeFamily, $typeName));
        }

        $class = sprintf('LongitudeOne\Spatial\PHP\Types\%s\%s', $typeFamily, constant($constName));

        return new $class($value['value'], $value['srid']);
    }
}
