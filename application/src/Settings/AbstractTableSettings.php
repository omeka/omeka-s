<?php
namespace Omeka\Settings;

use Omeka\Service\Exception;

/**
 * Abstract table settings.
 *
 * Extend this class to manage settings that are stored in separate tables.
 */
abstract class AbstractTableSettings extends AbstractSettings
{
    /**
     * @var int
     */
    protected $targetId;

    /**
     * Get the setting table name.
     *
     * @return string
     */
    abstract public function getTableName();

    /**
     * Get the ID column name of the setting table.
     *
     * @return string
     */
    abstract public function getIdColumnName();

    /**
     * Set the ID of the target entity.
     *
     * @param int $site
     */
    public function setTargetId($targetId)
    {
        if ($targetId !== $this->targetId) {
            $this->cache = null;
        }
        $this->targetId = $targetId;
    }

    protected function setCache()
    {
        if (!$this->targetId) {
            throw new Exception\RuntimeException('Cannot use table settings when no target ID is set.');
        }
        $sql = sprintf('SELECT * FROM %s WHERE %s = ?', $this->getTableName(), $this->getIdColumnName());
        $settings = $this->connection->fetchAll($sql, [$this->targetId]);
        foreach ($settings as $setting) {
            $this->cache[$setting['id']] = $this->connection->convertToPHPValue($setting['value'], 'json_array');
        }
    }

    protected function setSetting($id, $value)
    {
        if (!$this->targetId) {
            throw new Exception\RuntimeException('Cannot use table settings when no target ID is set.');
        }
        $sql = sprintf('SELECT * FROM %s WHERE id = ? AND %s = ?', $this->getTableName(), $this->getIdColumnName());
        $setting = $this->connection->fetchAssoc($sql, [$id, $this->targetId]);
        if ($setting) {
            $this->connection->update(
                $this->getTableName(),
                ['value' => $value],
                ['id' => $id, $this->getIdColumnName() => $this->targetId],
                ['json_array']
            );
        } else {
            $this->connection->insert(
                $this->getTableName(),
                ['value' => $value, $this->getIdColumnName() => $this->targetId, 'id' => $id],
                ['json_array', \PDO::PARAM_INT]
            );
        }
    }

    protected function deleteSetting($id)
    {
        if (!$this->targetId) {
            throw new Exception\RuntimeException('Cannot use table settings when no target ID is set.');
        }
        $this->connection->delete(
            $this->getTableName(),
            [$this->getIdColumnName() => $this->targetId, 'id' => $id],
            [\PDO::PARAM_INT]
        );
    }
}
