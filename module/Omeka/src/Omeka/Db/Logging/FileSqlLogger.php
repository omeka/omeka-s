<?php
namespace Omeka\Db\Logging;

use Doctrine\DBAL\Logging\SQLLogger;
use PDO;
use SplFileObject;

class FileSqlLogger implements SQLLogger
{
    protected $types = array(
        PDO::PARAM_BOOL => 'boolean',
        PDO::PARAM_NULL => 'null',
        PDO::PARAM_INT  => 'integer',
        PDO::PARAM_STR  => 'string',
    );

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
        $this->file->fwrite(date('c') . PHP_EOL);
        $this->file->fwrite($sql . PHP_EOL);
        foreach ($params as $key => $param) {
            $type = $this->getType($types[$key]);
            $param = var_export($param, true);
            $this->file->fwrite("($type) $param" . PHP_EOL);
        }
        $this->file->fwrite(PHP_EOL);
    }

    public function stopQuery()
    {
    }

    /**
     * Get the human readable parameter type.
     *
     * @param int|string $type
     * @return string
     */
    protected function getType($type)
    {
        if (is_string($type)) {
            return $type;
        }
        if (array_key_exists($type, $this->types)) {
            return $this->types[$type];
        }
        return $type;
    }
}
