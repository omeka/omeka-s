<?php
namespace Omeka\View\Helper;

use Omeka\Site\Navigation\Link\Manager;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering a navigation links.
 */
class NavigationLink extends AbstractHelper
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Construct the helper.
     *
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getTypes()
    {
        return $this->manager->getRegisteredNames();
    }

    public function getCustomTypes()
    {
        return array_diff($this->getTypes(), ['page']);
    }

    public function getName($type)
    {
        return $this->manager->get($type)->getName();
    }
}
