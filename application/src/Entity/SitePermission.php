<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"site_id", "user_id"}
 *         )
 *     }
 * )
 */
class SitePermission extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Site")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $site;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $admin = false;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $attach = false;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $edit = false;

    public function getId()
    {
        return $this->id;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setAdmin($admin)
    {
        $this->admin = (bool) $admin;
    }

    public function getAdmin()
    {
        return $this->admin;
    }

    public function setAttach($attach)
    {
        $this->attach = (bool) $attach;
    }

    public function getAttach()
    {
        return $this->attach;
    }

    public function setEdit($edit)
    {
        $this->edit = (bool) $edit;
    }

    public function getEdit()
    {
        return $this->edit;
    }
}
