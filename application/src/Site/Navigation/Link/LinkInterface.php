<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

interface LinkInterface
{
    /**
     * Get the link type name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get the view template used to render the link form.
     *
     * @return string
     */
    public function getFormTemplate();

    /**
     * Validate link data.
     *
     * @param array $data
     * @return bool
     */
    public function isValid(array $data, ErrorStore $errorStore);

    /**
     * Get the link label.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function getLabel(array $data, SiteRepresentation $site);

    /**
     * Translate from site navigation data to Zend Navigation configuration.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function toZend(array $data, SiteRepresentation $site);

    /**
     * Translate from site navigation data to jsTree configuration.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function toJstree(array $data, SiteRepresentation $site);
}
