<?php
namespace Omeka\Session\SaveHandler;

use Doctrine\DBAL\Connection;
use Zend\Session\SaveHandler\SaveHandlerInterface;

class Db implements SaveHandlerInterface
{
    /**
     * @var Doctrine\DBAL\Connection
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
     *
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open($savePath, $name)
    {
        $this->lifetime = ini_get('session.gc_maxlifetime');
        return true;
    }

    /**
     * Close session
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
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
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        $sql = 'INSERT INTO session (id, modified, data) VALUES (:id, :modified, :data) '
             . 'ON DUPLICATE KEY UPDATE modified = :modified, data = :data';
        return (bool) $this->conn->executeUpdate($sql, [
            'id' => $id, 'modified' => time(), 'data' => $data,
        ]);
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        return (bool) $this->conn->delete('session', ['id' => $id]);
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        $sql = 'DELETE FROM session WHERE modified < ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, (time() - $this->lifetime));
        return $stmt->execute();
    }
}
