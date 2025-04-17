<?php
namespace Omeka\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractEntity implements RoleInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=190, unique=true)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=190)
     */
    protected $name;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    protected $passwordHash;

    /**
     * @ORM\Column(type="string", length=190)
     */
    protected $role;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive = false;

    /**
     * @ORM\OneToMany(
     *     targetEntity="ApiKey",
     *     mappedBy="owner",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"},
     *     indexBy="id"
     * )
     */
    protected $keys;

    /**
     * @ORM\OneToMany(targetEntity="Site", mappedBy="owner")
     */
    protected $sites;

    /**
     * @ORM\OneToMany(targetEntity="Vocabulary", mappedBy="owner")
     */
    protected $vocabularies;

    /**
     * @ORM\OneToMany(targetEntity="ResourceClass", mappedBy="owner")
     */
    protected $resourceClasses;

    /**
     * @ORM\OneToMany(targetEntity="Property", mappedBy="owner")
     */
    protected $properties;

    /**
     * @ORM\OneToMany(targetEntity="ResourceTemplate", mappedBy="owner")
     */
    protected $resourceTemplates;

    public function __construct()
    {
        $this->keys = new ArrayCollection;
        $this->sites = new ArrayCollection;
        $this->vocabularies = new ArrayCollection;
        $this->resourceClasses = new ArrayCollection;
        $this->properties = new ArrayCollection;
        $this->resourceTemplates = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(DateTime $created)
    {
        $this->created = $created;
    }

    public function setModified(DateTime $dateTime)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Update the user's password, storing it hashed.
     *
     * @param string $password Password to set.
     */
    public function setPassword($password)
    {
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify that a given password is correct for the user.
     *
     * @param string $possiblePassword Password to check.
     * @return bool
     */
    public function verifyPassword($possiblePassword)
    {
        // If no password is set any is invalid
        if ($this->passwordHash === null) {
            return false;
        }

        return password_verify($possiblePassword, $this->passwordHash);
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
    }

    public function isActive()
    {
        return (bool) $this->isActive;
    }

    public function getKeys()
    {
        return $this->keys;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function getVocabularies()
    {
        return $this->vocabularies;
    }

    public function getResourceClasses()
    {
        return $this->resourceClasses;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getResourceTemplates()
    {
        return $this->resourceTemplates;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = $this->modified = new DateTime('now');
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $this->modified = new DateTime('now');
    }

    public function getRoleId()
    {
        return $this->getRole();
    }
}
