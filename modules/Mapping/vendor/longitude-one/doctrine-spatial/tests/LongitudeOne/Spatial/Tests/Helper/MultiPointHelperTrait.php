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

namespace LongitudeOne\Spatial\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\MultiPoint;
use LongitudeOne\Spatial\Tests\Fixtures\MultiPointEntity;

/**
 * MultipointPointHelperTrait Trait.
 *
 * This helper provides some methods to generates multipoint entities.
 * All of these points are defined in test documentation.
 *
 * Methods beginning with create will store a geo* entity in database.
 *
 * @see /docs/Test.rst
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @method EntityManagerInterface getEntityManager the entity interface
 *
 * @internal
 */
trait MultiPointHelperTrait
{
    use PointHelperTrait;

    /**
     * Create A Multipoint entity entity composed of four points and persist it in database.
     */
    protected function persistFourPoints(): MultiPointEntity
    {
        try {
            $multipoint = new MultiPoint([]);
            $multipoint->addPoint(static::createGeometryPoint('0 0', 0, 0));
            $multipoint->addPoint(static::createGeometryPoint('0 1', 0, 1));
            $multipoint->addPoint(static::createGeometryPoint('1 0', 1, 0));
            $multipoint->addPoint(static::createGeometryPoint('1 1', 0, 1));
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create a multipoint (0 0, 0 1, 1 0, 1 1): %s', $e->getMessage()));
        }

        return $this->createMultipoint($multipoint);
    }

    /**
     * Create A Multipoint entity entity composed of one point and persist it in database.
     */
    protected function persistSinglePoint(): MultiPointEntity
    {
        try {
            $multipoint = new MultiPoint([]);
            $multipoint->addPoint(static::createGeometryPoint('0 0', 0, 0));
        } catch (InvalidValueException $e) {
            static::fail(sprintf('Unable to create a multipoint (0 0): %s', $e->getMessage()));
        }

        return $this->createMultipoint($multipoint);
    }

    /**
     * Create a geometric MultiPoint entity from an array of geometric points.
     *
     * @param MultiPoint $multipoint Each point could be an array of X, Y or an instance of Point class
     */
    private function createMultipoint(MultiPoint $multipoint): MultiPointEntity
    {
        $multiPointEntity = new MultiPointEntity();
        $multiPointEntity->setMultiPoint($multipoint);
        $this->getEntityManager()->persist($multiPointEntity);
        $this->getEntityManager()->flush();

        return $multiPointEntity;
    }
}
