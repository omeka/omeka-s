<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;

interface LinkInterface
{
    /**
     * Get the default label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Get the link form.
     *
     * @param array $data
     * @return string
     */
    public function getForm(array $data);

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
