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
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;

/**
 * Spatial platform interface.
 */
interface PlatformInterface
{
    /**
     * Convert Binary to php value.
     *
     * @param AbstractSpatialType $type    Spatial type
     * @param string              $sqlExpr Sql expression
     *
     * @return GeometryInterface
     */
    public function convertBinaryToPhpValue(AbstractSpatialType $type, $sqlExpr);

    /**
     * Convert string data to a php value.
     *
     * @param AbstractSpatialType $type    The abstract spatial type
     * @param string              $sqlExpr the SQL expression
     *
     * @return GeometryInterface
     */
    public function convertStringToPhpValue(AbstractSpatialType $type, $sqlExpr);

    /**
     * Convert to database value.
     *
     * @param AbstractSpatialType $type  The spatial type
     * @param GeometryInterface   $value The geometry interface
     *
     * @return string
     */
    public function convertToDatabaseValue(AbstractSpatialType $type, GeometryInterface $value);

    /**
     * Convert to database value to SQL.
     *
     * @param AbstractSpatialType $type    The spatial type
     * @param string              $sqlExpr The SQL expression
     *
     * @return string
     */
    public function convertToDatabaseValueSql(AbstractSpatialType $type, $sqlExpr);

    /**
     * Convert to php value to SQL.
     *
     * @param AbstractSpatialType $type    The spatial type
     * @param string              $sqlExpr The SQL expression
     *
     * @return string
     */
    public function convertToPhpValueSql(AbstractSpatialType $type, $sqlExpr);

    /**
     * Get an array of database types that map to this Doctrine type.
     *
     * @param AbstractSpatialType $type the spatial type
     *
     * @return string[]
     */
    public function getMappedDatabaseTypes(AbstractSpatialType $type);

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration array SHALL contains 'type' as key
     *
     * @return string
     */
    public function getSqlDeclaration(array $fieldDeclaration);
}
