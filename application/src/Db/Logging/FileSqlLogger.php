<?php
namespace Omeka\Db\Logging;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use PDO;
use SplFileObject;

class FileSqlLogger implements SQLLogger
{
    /**
     * List of PDO and Doctrine built-in types
     */
    protected $types = [
        PDO::PARAM_BOOL => 'PARAM_BOOL',
        PDO::PARAM_NULL => 'PARAM_NULL',
        PDO::PARAM_INT => 'PARAM_INT',
        PDO::PARAM_STR => 'PARAM_STR',
        PDO::PARAM_LOB => 'PARAM_LOB',
        Connection::PARAM_INT_ARRAY => 'PARAM_INT_ARRAY',
        Connection::PARAM_STR_ARRAY => 'PARAM_STR_ARRAY',
    ];

    /**
     * @var SplFileObject
     */
    protected $file;

    /**
     * @var float
     */
    protected $startTime;

    /**
     * @var string
     */
    protected $entry;

    /**
     * Set the file object.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->file = new SplFileObject($path, 'a');
    }

    /**
     * Log SQL to file.
     *
     * @param string $sql
     * @param null|array $params
     * @param null|array $types
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $entry = $sql . PHP_EOL;

        if ($params) {
            foreach ($params as $index => $param) {
                $type = isset($types[$index]) ? $this->getType($types[$index]) : 'default';
                $entry .= sprintf('  %s: (%s) %s%s',
                    $index,
                    $type,
                    json_encode($param, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    PHP_EOL
                );
            }
        }

        $this->entry = $entry;
        $this->startTime = microtime(true);
    }

    public function stopQuery()
    {
        $duration = microtime(true) - $this->startTime;
        $entry = sprintf('%s (%0.3F)', date('c', $this->startTime), $duration) . PHP_EOL
            . $this->entry
            . PHP_EOL;
        $this->file->fwrite($entry);

        $this->startTime = null;
        $this->entry = null;
    }

    protected function getType($type)
    {
        if (array_key_exists($type, $this->types)) {
            return $this->types[$type];
        }
        return $type;
    }
}
