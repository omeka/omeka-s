<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Page extends AbstractLink
{
    public function getLabel()
    {
        return 'Page';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: page link missing label');
            return false;
        }
        if (!isset($data['id'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: page link missing page ID');
            return false;
        }
        if (!isset($data['pageSlug'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: page link missing page slug');
            return false;
        }
        if (!isset($data['pageTitle'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: page link missing page title');
            return false;
        }
        return true;
    }

    public function getForm(array $data, SiteRepresentation $site)
    {
        $pages = $site->pages();
        if (!isset($pages[$data['id']])) {
            // Handle an invalid page.
            $fallback = new Fallback('page');
            $fallback->setServiceLocator($this->getServiceLocator());
            return $fallback->getForm($data, $site);
        }

        $escape = $this->getViewHelper('escapeHtml');
        $page = sprintf('%s (%s)', $data['pageTitle'], $data['pageSlug']);
        return '<label>Type <input type="text" value="' . $escape($this->getLabel()) . '" disabled></label>'
            . '<label>Page <input type="text" value="' . $escape($page) . '" disabled></label>'
            . '<label>Label <input type="text" data-name="label" value="' . $escape($data['label']) . '"></label>';
    }

    public function toZend(array $data, Site $site)
    {
        $sitePage = $site->getPages()->get($data['id']);
        if (!$sitePage) {
            // Handle an invalid page.
            $fallback = new Fallback('page');
            $fallback->setServiceLocator($this->getServiceLocator());
            return $fallback->toZend($data, $site);
        }

        return [
            'label' => $data['label'],
            'route' => 'site/page',
            'params' => [
                'site-slug' => $site->getSlug(),
                'page-slug' => $sitePage->getSlug(),
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        $pages = $site->pages();
        if (!isset($pages[$data['id']])) {
            // Handle an invalid page.
            return $data;
        }

        $sitePage = $pages[$data['id']];
        $label = isset($data['label']) ? $data['label'] : $sitePage->title();
        return [
            'label' => $label,
            'id' => $sitePage->id(),
            'pageSlug' => $sitePage->slug(),
            'pageTitle' => $sitePage->title(),
        ];
    }
}
