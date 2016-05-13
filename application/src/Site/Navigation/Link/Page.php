<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Page implements LinkInterface
{
    public function getLabel()
    {
        return 'Page'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/page';
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

    public function toZend(array $data, SiteRepresentation $site)
    {
        $pages = $site->pages();
        if (!isset($pages[$data['id']])) {
            // Handle an invalid page.
            $fallback = new Fallback('page');
            return $fallback->toZend($data, $site);
        }
        $sitePage = $pages[$data['id']];

        return [
            'label' => $data['label'],
            'route' => 'site/page',
            'params' => [
                'site-slug' => $site->slug(),
                'page-slug' => $sitePage->slug(),
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
