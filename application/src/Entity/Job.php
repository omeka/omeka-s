<?php
namespace Omeka\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Job extends AbstractEntity
{
    /**#@+
     * Job statuses
     *
     * STATUS_STARTING:    The job was dispatched.
     * STATUS_STOPPING:    The job is currently stopping.
     * STATUS_IN_PROGRESS: The job was sent and is in progress.
     * STATUS_COMPLETED:   The job was performed and is sucessfully completed.
     * STATUS_STOPPED:     The job was stopped and most likely incomplete.
     * STATUS_ERROR:       There was an unrecoverable error during the job.
     */
    const STATUS_STARTING = 'starting'; // @translate
    const STATUS_STOPPING = 'stopping'; // @translate
    const STATUS_IN_PROGRESS = 'in_progress'; // @translate
    const STATUS_COMPLETED = 'completed'; // @translate
    const STATUS_STOPPED = 'stopped'; // @translate
    const STATUS_ERROR = 'error'; // @translate
    /**#@-*/

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(nullable=true)
     */
    protected $pid;

    /**
     * @Column(nullable=true)
     */
    protected $status;

    /**
     * @Column
     */
    protected $class;

    /**
     * @Column(type="json_array", nullable=true)
     */
    protected $args;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $log;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @Column(type="datetime")
     */
    protected $started;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $ended;

    public function getId()
    {
        return $this->id;
    }

    public function setPid($pid)
    {
        $this->pid = is_null($pid) ? null : trim($pid);
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setClass($class)
    {
        $this->class = trim($class);
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setLog($log)
    {
        $this->log = $log;
    }

    public function addLog($log)
    {
        $this->log .= $log . PHP_EOL;
    }

    public function getLog()
    {
        return $this->log;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setStarted(DateTime $started)
    {
        $this->started = $started;
    }

    public function getStarted()
    {
        return $this->started;
    }

    public function setEnded(DateTime $ended)
    {
        $this->ended = $ended;
    }

    public function getEnded()
    {
        return $this->ended;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->started = new DateTime('now');
    }
}
