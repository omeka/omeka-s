<?php
namespace Omeka\Authentication\Storage;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use Laminas\Authentication\Storage\StorageInterface;

/**
 * Auth storage wrapper for doctrine objects.
 *
 * Stores the ID instead of the full object, translates between ID and object
 * automatically on read/write.
 */
class DoctrineWrapper implements StorageInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * Cached identity lookup result
     *
     * False (the default) indicates no cached result, and the lookup must be
     * performed against the database. Null indicates a cached result of no
     * identity (no user logged in or the user was invalid), and the user
     * entity object is stored here if a user is logged in.
     *
     * @var Omeka\Entity|null|bool
     */
    protected $cachedIdentity = false;

    /**
     * Create the wrapper around the given storage method, looking up users
     * from the given repository.
     *
     * @param StorageInterface $storage "Base" storage class
     * @param EntityRepository $repository Repository storing Users
     */
    public function __construct(StorageInterface $storage, EntityRepository $repository)
    {
        $this->setStorage($storage);
        $this->setRepository($repository);
    }

    public function isEmpty()
    {
        if ($this->storage->isEmpty()) {
            return true;
        }
        if (null === $this->read()) {
            // An identity may exist in a cookie but not in the database.
            return true;
        }
        return false;
    }

    public function read()
    {
        if ($this->cachedIdentity !== false) {
            return $this->cachedIdentity;
        }

        $identity = null;
        $id = $this->storage->read();
        if ($id) {
            try {
                $identity = $this->repository->findOneBy(['id' => $id, 'isActive' => true]);
            } catch (DBALException $e) {
                // The user table does not exist.
            }
        }

        $this->cachedIdentity = $identity;
        return $identity;
    }

    public function write($identity)
    {
        $this->cachedIdentity = $identity;
        $this->storage->write($identity->getId());
    }

    public function clear()
    {
        $this->cachedIdentity = false;
        $this->storage->clear();
    }

    /**
     * Set the base storage class to wrap.
     *
     * @param StorageInterface $storage
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get the storage class being wrapped.
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Set the repository for looking up User objects.
     *
     * @param EntityRepository $repository
     */
    public function setRepository(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get the repository for looking up User objects.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
