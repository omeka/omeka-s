<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering the password requirements.
 */
class PasswordRequirements extends AbstractHelper
{
    /**
     * @var array
     */
    protected $passwordConfig;

    /**
     * @param array $passwordConfig
     */
    public function __construct(array $passwordConfig)
    {
        $this->passwordConfig = $passwordConfig;
    }

    /**
     * @return array
     */
    public function getPasswordConfig()
    {
        return $this->passwordConfig;
    }

    public function __invoke()
    {
        $view = $this->getView();
        $config = $this->getPasswordConfig();

        $requirements = [];
        if (isset($config['min_length']) && is_numeric($config['min_length'])) {
            $requirements[] = sprintf(
                $view->translate('Password must be a minimum of %s characters in length.'),
                $config['min_length']
            );
        }
        if (isset($config['min_lowercase']) && is_numeric($config['min_lowercase'])) {
            $requirements[] = sprintf(
                $view->translate('Password must contain at least %s lowercase characters.'),
                $config['min_lowercase']
            );
        }
        if (isset($config['min_uppercase']) && is_numeric($config['min_uppercase'])) {
            $requirements[] = sprintf(
                $view->translate('Password must contain at least %s uppercase characters.'),
                $config['min_uppercase']
            );
        }
        if (isset($config['min_number']) && is_numeric($config['min_number'])) {
            $requirements[] = sprintf(
                $view->translate('Password must contain at least %s numbers.'),
                $config['min_number']
            );
        }
        if (isset($config['min_symbol']) && is_numeric($config['min_symbol'])
            && isset($config['symbol_list']) && is_string($config['symbol_list'])
            && strlen($config['symbol_list'])
        ) {
            $requirements[] = sprintf(
                $view->translate('Password must contain at least %1$s symbols: <code>%2$s</code>'),
                $config['min_symbol'],
                $config['symbol_list']
            );
        }
        if (!$requirements) {
            return;
        }
        $html = '<ul>';
        foreach ($requirements as $requirement) {
            $html .= sprintf('<li>%s</li>', $requirement);
        }
        $html .= '</ul>';
        return $html;
    }
}
