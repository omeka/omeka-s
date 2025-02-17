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

/**
 * PersistHelper Trait.
 *
 * This helper provides some methods to persist entity then it test to find it.
 * All of these points are defined in test documentation.
 *
 * Methods beginning with create will store a geo* entity in database.
 *
 * @see /docs/Test.rst
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @internal
 *
 * @method EntityManagerInterface getEntityManager retrieve entity manager
 */
trait PersistHelperTrait
{
    /**
     * Store and retrieve geography entity in database and retrieve it by its geometry (or geography).
     *
     * Then assert data are equals, not same.
     *
     * @param EntityManagerInterface $entityManager Entity manager to persist data
     * @param object                 $entity        Entity to test
     * @param object                 $geo           Geography or geometry object (non-persisted object)
     * @param string                 $method        the method name to retrieve object
     */
    private static function assertIsRetrievableByGeo(
        EntityManagerInterface $entityManager,
        object $entity,
        object $geo,
        string $method
    ): array {
        $entityManager->persist($entity);
        $entityManager->flush();

        $queryEntity = $entityManager->getRepository(get_class($entity))->{$method}($geo);

        static::assertCount(1, $queryEntity);
        static::assertEquals($entity, $queryEntity[0]);

        return $queryEntity;
    }

    /**
     * Store geography entity in database and retrieve it by its id.
     *
     * Then assert data are equals, not same.
     *
     * @param EntityManagerInterface $entityManager Entity manager to persist data
     * @param object                 $entity        Entity to test
     */
    private static function assertIsRetrievableById(EntityManagerInterface $entityManager, object $entity): ?object
    {
        $entityManager->persist($entity);
        $entityManager->flush();

        $id = $entity->getId();

        $queryEntity = $entityManager->getRepository(get_class($entity))->find($id);

        static::assertEquals($entity, $queryEntity);

        return $queryEntity;
    }
}
