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

use LongitudeOne\Spatial\DBAL\Types\AbstractSpatialType;
use LongitudeOne\Spatial\DBAL\Types\GeographyType;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;

/**
 * PostgreSql spatial platform.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT */
class PostgreSql extends AbstractPlatform
{
    public const DEFAULT_SRID = 4326;

    /**
     * Convert Binary to php value.
     *
     * @param AbstractSpatialType $type    Spatial type
     * @param string              $sqlExpr Sql expression
     *
     * @throws InvalidValueException when SQL expression is not a resource
     *
     * @return GeometryInterface
     */
    public function convertBinaryToPhpValue(AbstractSpatialType $type, $sqlExpr)
    {
        if (!is_resource($sqlExpr)) {
            throw new InvalidValueException(sprintf('Invalid resource value "%s"', $sqlExpr));
        }

        $sqlExpr = stream_get_contents($sqlExpr);

        return parent::convertBinaryToPhpValue($type, $sqlExpr);
    }

    /**
     * Convert to database value.
     *
     * @param AbstractSpatialType $type  The spatial type
     * @param GeometryInterface   $value The geometry interface
     *
     * @return string
     */
    public function convertToDatabaseValue(AbstractSpatialType $type, GeometryInterface $value)
    {
        $sridSQL = null;

        if ($type instanceof GeographyType && null === $value->getSrid()) {
            $value->setSrid(self::DEFAULT_SRID);
        }

        $srid = $value->getSrid();
        if (null !== $srid || $type instanceof GeographyType) {
            $sridSQL = sprintf('SRID=%d;', $srid);
        }

        return sprintf('%s%s', $sridSQL, parent::convertToDatabaseValue($type, $value));
    }

    /**
     * Convert to database value to SQL.
     *
     * @param AbstractSpatialType $type    The spatial type
     * @param string              $sqlExpr The SQL expression
     *
     * @return string
     */
    public function convertToDatabaseValueSql(AbstractSpatialType $type, $sqlExpr)
    {
        if ($type instanceof GeographyType) {
            return sprintf('ST_GeographyFromText(%s)', $sqlExpr);
        }

        return sprintf('ST_GeomFromEWKT(%s)', $sqlExpr);
    }

    /**
     * Convert to php value to SQL.
     *
     * @param AbstractSpatialType $type    The spatial type
     * @param string              $sqlExpr The SQL expression
     *
     * @return string
     */
    public function convertToPhpValueSql(AbstractSpatialType $type, $sqlExpr)
    {
        if ($type instanceof GeographyType) {
            return sprintf('ST_AsEWKT(%s)', $sqlExpr);
        }

        return sprintf('ST_AsEWKB(%s)', $sqlExpr);
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration array SHALL contains 'type' as key
     *
     * @return string
     */
    public function getSqlDeclaration(array $fieldDeclaration)
    {
        $typeFamily = $fieldDeclaration['type']->getTypeFamily();
        $sqlType = $fieldDeclaration['type']->getSQLType();

        if ($typeFamily === $sqlType) {
            return $sqlType;
        }

        if (isset($fieldDeclaration['srid'])) {
            return sprintf('%s(%s,%d)', $typeFamily, $sqlType, $fieldDeclaration['srid']);
        }

        return sprintf('%s(%s)', $typeFamily, $sqlType);
    }
}
