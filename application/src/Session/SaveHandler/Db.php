<?php
namespace Omeka\Session\SaveHandler;

use Doctrine\DBAL\Connection;
use Laminas\Session\SaveHandler\SaveHandlerInterface;

class Db implements SaveHandlerInterface
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var int Session lifetime
     */
    protected $lifetime;

    /**
     * @var array
     */
    protected $foundRows = [];

    /**
     * Constructor
     *
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Open session
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $name)
    {
        $this->lifetime = ini_get('session.gc_maxlifetime');
        return true;
    }

    /**
     * Close session
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
        if (isset($this->foundRows[$id])) {
            $session = $this->foundRows[$id];
            unset($this->foundRows[$id]);
        } else {
            $session = $this->doRead($id);
        }

        if ($session) {
            if (($session['modified'] + $this->lifetime) > time()) {
                return $session['data'];
            }
            $this->destroy($id);
        }
        return '';
    }

    /**
     * Write session data
     */
    #[\ReturnTypeWillChange]
    public function write($id, $data)
    {
        $sql = 'INSERT INTO session (id, modified, data) VALUES (:id, :modified, :data) '
             . 'ON DUPLICATE KEY UPDATE modified = :modified, data = :data';
        $this->conn->executeStatement($sql, [
            'id' => $id, 'modified' => time(), 'data' => $data,
        ]);
        return true;
    }

    /**
     * Destroy session
     */
    #[\ReturnTypeWillChange]
    public function destroy($id)
    {
        return (bool) $this->conn->delete('session', ['id' => $id]);
    }

    /**
     * Garbage Collection
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        $sql = 'DELETE FROM session WHERE modified < ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, (time() - $this->lifetime));
        return $stmt->executeStatement();
    }

    /**
     * Create a new session ID
     *
     * Uses PHP's default ID generation.
     *
     * @return string
     */
    public function create_sid()
    {
        return session_create_id();
    }

    /**
     * Validate whether the given session ID exists
     *
     * @param string $id
     * @return bool
     */
    public function validateId($id)
    {
        $session = $this->doRead($id);
        if ($session) {
            $this->foundRows[$id] = $session;
            return true;
        }
        return false;
    }

    /**
     * Attempt to read the given session id's row
     *
     * @param string $id
     * @return array|false
     */
    protected function doRead($id)
    {
        return $this->conn->fetchAssociative('SELECT * FROM session WHERE id = ?', [$id]);
    }
}
