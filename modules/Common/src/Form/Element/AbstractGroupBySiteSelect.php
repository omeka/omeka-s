<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\Select;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

abstract class AbstractGroupBySiteSelect extends Select
{
    use TraitOptionalElement;
    use TraitPrependValuesOptions;

    /**
     * @var SiteRepresentation
     */
    protected $site;

    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function setSite(?SiteRepresentation $site): self
    {
        $this->site = $site;
        return $this;
    }

    public function getSite(): ?SiteRepresentation
    {
        return $this->site;
    }

    public function setApiManager(ApiManager $apiManager): self
    {
        $this->apiManager = $apiManager;
        return $this;
    }

    public function getApiManager(): ApiManager
    {
        return $this->apiManager;
    }

    /**
     * Get the resource name.
     */
    abstract public function getResourceName(): string;

    /**
     * Get the value label from a resource.
     *
     * @param $resource
     * @return string
     */
    abstract public function getValueLabel(SitePageRepresentation $resource): string;

    /**
     * Specific options:
     * - query;
     * - site_group;
     * - exclude_current_site;
     * - disable_group_by_site.
     *
     * {@inheritDoc}
     * @see \Laminas\Form\Element\Select::getValueOptions()
     */
    public function getValueOptions(): array
    {
        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }

        $currentSite = $this->getSite();
        if ($currentSite) {
            $currentSiteSlug = $currentSite->slug();
            $excludeCurrentSite = $currentSite && $this->getOption('exclude_current_site');
        } else {
            $currentSiteSlug = null;
            $excludeCurrentSite = false;
        }

        // Currently, the response cannot manage the option to exclude a site.
        $response = $this->getApiManager()->search($this->getResourceName(), $query);

        $siteGroup = $this->listSiteGroup();
        $withinSiteGroup = $siteGroup !== null;
        if ($excludeCurrentSite && $siteGroup) {
            unset($siteGroup[$currentSiteSlug]);
        }

        $disableGroupBySite = (bool) $this->getOption('disable_group_by_site');
        if ($disableGroupBySite) {
            // Group alphabetically by resource label without grouping by site.
            $resources = [];
            foreach ($response->getContent() as $resource) {
                $site = $resource->site();
                $siteSlug = $site ? $site->slug() : null;
                if ($withinSiteGroup && !isset($siteGroup[$siteSlug])) {
                    continue;
                } elseif ($excludeCurrentSite && $siteSlug === $currentSiteSlug) {
                    continue;
                }
                $resources[$this->getValueLabel($resource)][] = $resource->id();
            }

            ksort($resources);
            $valueOptions = [];
            foreach ($resources as $label => $ids) {
                foreach ($ids as $id) {
                    $valueOptions[$id] = $label;
                }
            }
        } else {
            $currentSiteSlug = $currentSite->slug();
            // Group alphabetically by site title (but use slugs as keys).
            $resourceSites = [];
            $resourceSiteTitles = [];
            foreach ($response->getContent() as $resource) {
                $site = $resource->site();
                $siteSlug = $site ? $site->slug() : null;
                if ($withinSiteGroup && !isset($siteGroup[$siteSlug])) {
                    continue;
                } elseif ($excludeCurrentSite && $siteSlug === $currentSiteSlug) {
                    continue;
                }
                $resourceSites[$siteSlug]['site'] = $site;
                $resourceSites[$siteSlug]['resources'][] = $resource;
                $resourceSiteTitles[$siteSlug] = $site ? $site->title() : null;
            }
            natcasesort($resourceSiteTitles);
            $resourceSites = array_replace($resourceSiteTitles, $resourceSites);

            $valueOptions = [];
            foreach ($resourceSites as $resourceSite) {
                $options = [];
                foreach ($resourceSite['resources'] as $resource) {
                    $options[$resource->id()] = $this->getValueLabel($resource);
                    if (!$options) {
                        continue;
                    }
                }
                $site = $resourceSite['site'];
                if ($site instanceof SiteRepresentation) {
                    $label = $site->isPublic() ? $site->title() : ($site->title() . ' *');
                }
                // Is it really possible? Not important anyway.
                else {
                    $label = '[No site]'; // @translate
                }
                $valueOptions[] = ['label' => $label, 'options' => $options];
            }
        }

        return $this->prependValuesOptions($valueOptions);
    }

    /**
     * List the site groups of the current site.
     *
     * @todo Use something cleaner than a setting name in option?
     *
     * @return array|null Group of the current site.
     */
    protected function listSiteGroup(): ?array
    {
        $siteGroup = $this->getOption('site_group');
        if (!$siteGroup) {
            return null;
        }

        $site = $this->getSite();
        if (empty($site)) {
            return null;
        }

        $slug = $site->slug();

        $settings = $site->getServiceLocator()->get('Omeka\Settings');
        $siteGroups = $settings->get($siteGroup);
        if (empty($siteGroups)) {
            return [$slug => $slug];
        }

        return empty($siteGroups) || empty($siteGroups[$slug])
            ? [$slug => $slug]
            : array_combine($siteGroups[$slug], $siteGroups[$slug]);
    }
}
