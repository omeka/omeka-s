<?php
namespace Omeka\BlockLayout;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

abstract class AbstractBlockLayout implements BlockLayoutInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function prepareForm(PhpRenderer $view)
    {}

    /**
     * {@inheritDoc}
     */
    public function prepareRender(PhpRenderer $view)
    {}

    /**
     * {@inheritDoc}
     */
    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    /**
     * Return block data by key.
     *
     * @param array $data The block data
     * @param string $key The data key
     * @param mixed $default Return this if key does not exist
     * @return mixed
     */
    public function getData(array $data, $key, $default = null)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
}
