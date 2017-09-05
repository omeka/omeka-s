<?php
namespace Omeka\Settings;

use Omeka\Service\Exception;

/**
 * Manage settings in a differentiated setting table.
 */
abstract class AbstractTargetSettings extends AbstractSettings
{
    /**
     * @var int
     */
    protected $targetId;

    /**
     * Get the target ID column name of the setting table.
     *
     * @return string
     */
    abstract public function getTargetIdColumnName();

    /**
     * Set the ID of the target entity.
     *
     * @param int $targetId
     */
    public function setTargetId($targetId)
    {
        if ($targetId !== $this->targetId) {
            $this->cache = null;
        }
        $this->targetId = $targetId;
    }

    /**
     * Set a setting
     *
     * Allows consumers to temporarily overwrite the target ID.
     *
     * @param string $id
     * @param mixed $value
     * @param int $targetId
     */
    public function set($id, $value, $targetId = null)
    {
        $originalTargetId = $this->targetId;
        if ($targetId) {
            $this->setTargetId($targetId);
        }
        parent::set($id, $value);
        $this->setTargetId($originalTargetId);
    }

    /**
     * Get a setting
     *
     * Allows consumers to temporarily overwrite the target ID.
     *
     * @param string $id
     * @param mixed $default
     * @param int $targetId
     * @return mixed
     */
    public function get($id, $default = null, $targetId = null)
    {
        $originalTargetId = $this->targetId;
        if ($targetId) {
            $this->setTargetId($targetId);
        }
        $setting = parent::get($id, $default);
        $this->setTargetId($originalTargetId);
        return $setting;
    }

    /**
     * Delete a setting
     *
     * Allows consumers to temporarily overwrite the target ID.
     *
     * @param string $id
     * @param int $targetId
     */
    public function delete($id, $targetId = null)
    {
        $originalTargetId = $this->targetId;
        if ($targetId) {
            $this->setTargetId($targetId);
        }
        parent::delete($id);
        $this->setTargetId($originalTargetId);
    }

    protected function setCache()
    {
        if (!$this->targetId) {
            throw new Exception\RuntimeException('Cannot manage settings when no target ID is set.');
        }
        $sql = sprintf('SELECT * FROM %s WHERE %s = ?', $this->getTableName(), $this->getTargetIdColumnName());
        $settings = $this->connection->fetchAll($sql, [$this->targetId]);
        foreach ($settings as $setting) {
            $this->cache[$setting['id']] = $this->connection->convertToPHPValue($setting['value'], 'json_array');
        }
    }

    protected function setSetting($id, $value)
    {
        if (!$this->targetId) {
            throw new Exception\RuntimeException('Cannot manage a settings when no target ID is set.');
        }
        $sql = sprintf('SELECT * FROM %s WHERE id = ? AND %s = ?', $this->getTableName(), $this->getTargetIdColumnName());
        $setting = $this->connection->fetchAssoc($sql, [$id, $this->targetId]);
        if ($setting) {
            $this->connection->update(
                $this->getTableName(),
                ['value' => $value],
                ['id' => $id, $this->getTargetIdColumnName() => $this->targetId],
                ['json_array']
            );
        } else {
            $this->connection->insert(
                $this->getTableName(),
                ['value' => $value, $this->getTargetIdColumnName() => $this->targetId, 'id' => $id],
                ['json_array', \PDO::PARAM_INT]
            );
        }
    }

    protected function deleteSetting($id)
    {
        if (!$this->targetId) {
            throw new Exception\RuntimeException('Cannot manage settings when no target ID is set.');
        }
        $this->connection->delete(
            $this->getTableName(),
            [$this->getTargetIdColumnName() => $this->targetId, 'id' => $id],
            [\PDO::PARAM_INT]
        );
    }
}
