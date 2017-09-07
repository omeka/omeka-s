<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting theme settings.
 */
class ThemeSetting extends AbstractHelper
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Construct the helper.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get a setting
     *
     * By default, will return null if no setting exists with the passed ID, but the default
     * can be changed by passing the second argument
     *
     * @param string $id
     * @param mixed $default
     * @return mixed
     */
    public function __invoke($id, $default = null)
    {
        if (isset($this->settings[$id])) {
            return $this->settings[$id];
        }

        return $default;
    }
}
