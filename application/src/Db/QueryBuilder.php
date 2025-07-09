<?php
namespace Omeka\Db;

use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;

class QueryBuilder extends DoctrineQueryBuilder
{
    /**
     * @var int A unique index for query builder placeholders and aliases.
     */
    protected $index = 0;

    /**
     * Create a unique named parameter, exclusive to this query builder.
     *
     * @param mixed $value The value to bind
     * @return string The placeholder
     */
    public function createNamedParameter($value)
    {
        $placeholder = sprintf('omeka_%s', $this->index++);
        $this->setParameter($placeholder, $value);
        return sprintf(':%s', $placeholder);
    }

    /**
     * Create a unique alias, exclusive to this query builder.
     *
     * @return string The alias
     */
    public function createAlias()
    {
        return sprintf('omeka_%s', $this->index++);
    }
}
