<?php
/**
 * This file is part of the doctrine spatial extension.
 *
 * PHP 7.4 | 8.0 | 8.1
 *
 * (c) Alexandre Tranchant <alexandre.tranchant@gmail.com> 2017 - 2022
 * (c) Longitude One 2020 - 2022
 * (c) 2015 Derek J. Lambert
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace LongitudeOne\Spatial\Tests;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Simple SQLLogger to log to file.
 */
class FileSQLLogger implements SQLLogger
{
    protected string $filename;

    /**
     * FileSQLLogger constructor.
     *
     * @param string $filename the filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string              $sql    the SQL to be executed
     * @param array|null          $params the SQL parameters
     * @param int[]|string[]|null $types  the SQL parameter types
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        file_put_contents($this->filename, $sql.PHP_EOL, FILE_APPEND);

        if ($params) {
            file_put_contents($this->filename, var_export($params, true).PHP_EOL, FILE_APPEND);
        }

        if ($types) {
            file_put_contents($this->filename, var_export($types, true).PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     */
    public function stopQuery()
    {
    }
}
