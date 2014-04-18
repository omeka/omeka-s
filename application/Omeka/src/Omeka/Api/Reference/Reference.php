<?php
namespace Omeka\Api\Reference;

use Omeka\Api\Adapter\AdapterInterface;

class Reference implements ReferenceInterface
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array(
            '@id' => $this->getApiUrl(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiUrl()
    {
        return $this->adapter->getApiUrl($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebUrl()
    {
        return $this->adapter->getWebUrl($this->data);
    }
}
