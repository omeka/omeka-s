<?php
namespace Omeka\Db\Logging;

use Doctrine\DBAL\Logging\SQLLogger;
use SplFileObject;

class FileSqlLogger implements SQLLogger
{
    /**
     * @var SplFileObject
     */
    protected $file;

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
        $this->file->fwrite(
            date('c') . PHP_EOL
          . $sql . PHP_EOL
          . var_export($params, true) . PHP_EOL
          . var_export($types, true) . PHP_EOL
          . PHP_EOL
        );
    }

    public function stopQuery()
    {}
}
