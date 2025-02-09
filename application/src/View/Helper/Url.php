<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\Url as LaminasUrl;

/**
 * Override of the default Laminas Url helper
 *
 * Replaces the default force_canonical implementation with usage of the serverUrl helper
 */
class Url extends LaminasUrl
{
    public function __construct(LaminasUrl $baseUrlHelper)
    {
        $this->router = $baseUrlHelper->router;
        $this->routeMatch = $baseUrlHelper->routeMatch;
    }

    public function __invoke($name = null, $params = [], $options = [], $reuseMatchedParams = false)
    {
        // This is also done in the base Url helper; we need to do it here also because
        // passing through another call messes up func_num_args
        if (3 === func_num_args() && is_bool($options)) {
            $reuseMatchedParams = $options;
            $options = [];
        }

        $forceCanonical = false;
        if (isset($options['force_canonical']) && $options['force_canonical']) {
            unset($options['force_canonical']);
            $forceCanonical = true;
        }

        $url = parent::__invoke($name, $params, $options, $reuseMatchedParams);

        if ($forceCanonical) {
            return $this->getView()->serverUrl($url);
        }
        return $url;
    }
}
