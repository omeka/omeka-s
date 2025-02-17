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

namespace LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql;

use LongitudeOne\Spatial\ORM\Query\AST\Functions\AbstractSpatialDQLFunction;

/**
 * MBRWithin DQL function
 * Whether MBR of one geometry is within MBR of another.
 *
 * @author  Mohammad Heydari <mdh.heydari@gmail.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://mdhheydari.mit-license.org MIT
 */
class SpMbrWithin extends AbstractSpatialDQLFunction
{
    /**
     * Function SQL name getter.
     *
     * @since 2.0 This function replace the protected property functionName.
     */
    protected function getFunctionName(): string
    {
        return 'MBRWithin';
    }

    /**
     * Maximum number of parameter for the spatial function.
     *
     * @since 2.0 This function replace the protected property maxGeomExpr.
     *
     * @return int the inherited methods shall NOT return null, but 0 when function has no parameter
     */
    protected function getMaxParameter(): int
    {
        return 2;
    }

    /**
     * Minimum number of parameter for the spatial function.
     *
     * @since 2.0 This function replace the protected property minGeomExpr.
     *
     * @return int the inherited methods shall NOT return null, but 0 when function has no parameter
     */
    protected function getMinParameter(): int
    {
        return 2;
    }

    /**
     * Get the platforms accepted.
     *
     * @since 2.0 This function replace the protected property platforms.
     *
     * @return string[] a non-empty array of accepted platforms
     */
    protected function getPlatforms(): array
    {
        return ['mysql'];
    }
}
