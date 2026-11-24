<?php
namespace Omeka\Db\Logging;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\ParameterType;
use SplFileObject;

class FileSqlLogger implements SQLLogger
{
    /**
     * List of Doctrine built-in types
     */
    protected $types = [
        ParameterType::BOOLEAN => 'PARAM_BOOL',
        ParameterType::NULL => 'PARAM_NULL',
        ParameterType::INTEGER => 'PARAM_INT',
        ParameterType::STRING => 'PARAM_STR',
        ParameterType::LARGE_OBJECT => 'PARAM_LOB',
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
    public function startQuery($sql, ?array $params = null, ?array $types = null)
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
        $entry = sprintf('%s (%0.3F)', date('c', (int) $this->startTime), $duration) . PHP_EOL
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
