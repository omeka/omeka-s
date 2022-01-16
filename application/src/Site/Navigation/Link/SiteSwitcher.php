<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Stdlib\ErrorStore;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Manager;

class SiteSwitcher implements LinkInterface
{
    /**
     * Get the link type name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Site switcher'; // @translate
    }

    /**
     * Get the view template used to render the link form.
     *
     * @return string
     */
    public function getFormTemplate()
    {
        return 'common/navigation-link-form/site-switcher';
    }

    /**
     * Validate link data.
     *
     * @param array $data
     * @return bool
     */
    public function isValid(array $data, ErrorStore $errorStore)
    {
        return (!isset($data['target-site-id']) || '' === trim($data['target-site-id']));
    }

    /**
     * Get the link label.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function getLabel(array $data, SiteRepresentation $site)
    {
        return isset($data['label']) && '' !== trim($data['label']) ? $data['label'] : null;
    }

    /**
     * Get site ID.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function getTargetSiteId(array $data, SiteRepresentation $site)
    {
        return isset($data['target-site-id']) && '' !== trim($data['target-site-id']) ? $data['target-site-id'] : null;
    }

    /**
     * Translate from site navigation data to Zend Navigation configuration.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function toZend(array $data, SiteRepresentation $site)
    {
        if (!$this->getTargetSiteId($data, $site)) {
            return[];
        }

        $path = $_SERVER["REQUEST_URI"];
        /** @var Manager $api */
        $api = $site->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->read('sites', ['id' => $data['target-site-id']]);
        if ($targetSite = $response->getContent()) {
            $path = preg_replace('/^\/s\/' . $site->slug() . '/', '/s/' . $targetSite->slug(), $path);
        } else {
            $path = '#';
        }

        $result = [
            'uri' => $path,
        ];

        return $result;
    }

    /**
     * Translate from site navigation data to jsTree configuration.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function toJstree(array $data, SiteRepresentation $site)
    {
        $result = [
            'label' => $data['label'],
            'target-site-id' => $data['target-site-id'],
        ];

        return $result;
    }
}
