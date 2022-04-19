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
    public function open(string $savePath, string $name): bool
    {
        $this->lifetime = ini_get('session.gc_maxlifetime');
        return true;
    }

    /**
     * Close session
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Read session data
     */
    public function read(string $id): string
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
    public function write(string $id, string $data): bool
    {
        $sql = 'INSERT INTO session (id, modified, data) VALUES (:id, :modified, :data) '
             . 'ON DUPLICATE KEY UPDATE modified = :modified, data = :data';
        return (bool) $this->conn->executeUpdate($sql, [
            'id' => $id, 'modified' => time(), 'data' => $data,
        ]);
    }

    /**
     * Destroy session
     */
    public function destroy(string $id): bool
    {
        return (bool) $this->conn->delete('session', ['id' => $id]);
    }

    /**
     * Garbage Collection
     */
    #[\ReturnTypeWillChange]
    public function gc(int $maxlifetime)
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
