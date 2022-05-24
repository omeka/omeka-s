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
        $session = $this->conn->fetchAssoc('SELECT * FROM session WHERE id = ?', [$id]);
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
        if ($stmt->execute()) {
            return $stmt->rowCount();
        } else {
            return false;
        }
    }
}
