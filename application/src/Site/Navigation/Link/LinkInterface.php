<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

interface LinkInterface
{
    /**
     * Get the default label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Validate link data.
     *
     * @param array $data
     * @return bool
     */
    public function isValid(array $data, ErrorStore $errorStore);

    /**
     * Get the link form.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return string
     */
    public function getForm(array $data, SiteRepresentation $site);

    /**
     * Translate from site navigation data to Zend Navigation configuration.
     *
     * @param array $data
     * @param Site $site
     * @return array
     */
    public function toZend(array $data, Site $site);

    /**
     * Translate from site navigation data to jsTree configuration.
     *
     * @param array $data
     * @param SiteRepresentation $site
     * @return array
     */
    public function toJstree(array $data, SiteRepresentation $site);
}
