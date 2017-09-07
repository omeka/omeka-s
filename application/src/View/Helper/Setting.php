<?php
namespace Omeka\View\Helper;

use Omeka\Settings\SettingsInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting settings.
 */
class Setting extends AbstractHelper
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * Construct the helper.
     *
     * @param SettingsInterface $settings
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get a setting
     *
     * Will return null if no setting exists with the passed ID.
     *
     * @param string $id
     * @param mixed $default
     * @param int $targetId
     * @return mixed
     */
    public function __invoke($id, $default = null, $targetId = null)
    {
        return $this->settings->get($id, $default, $targetId);
    }
}
