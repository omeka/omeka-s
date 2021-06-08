<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Stdlib\ErrorStore;
use Omeka\Api\Representation\SiteRepresentation;

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
        // TODO: Check for site slug? Check for empty label?
        return true;
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
     * Get site slug.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function getSiteSlug(array $data, SiteRepresentation $site)
    {
        return isset($data['site-slug']) && '' !== trim($data['site-slug']) ? $data['site-slug'] : null;
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
        $path = $_SERVER["REQUEST_URI"];

        $path = preg_replace('/^\/s\/' . $site->slug() . '/', '/s/' . $data['site-slug'], $path);

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
            'site-slug' => $data['site-slug'],
        ];

        return $result;
    }
}
