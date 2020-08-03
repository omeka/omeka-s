<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Page implements LinkInterface
{
    public function getName()
    {
        return 'Page'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/page';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: page link missing page ID');
            return false;
        }
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        if (isset($data['label']) && '' !== trim($data['label'])) {
            return $data['label'];
        }

        $pages = $site->pages();
        if (!isset($pages[$data['id']])) {
            $translator = $site->getServiceLocator()->get('MvcTranslator');
            return $translator->translate('[Missing Page]'); // @translate
        }
        $sitePage = $pages[$data['id']];

        // Handle a private page.
        if (!$sitePage->userIsAllowed('read')) {
            $translator = $site->getServiceLocator()->get('MvcTranslator');
            return $translator->translate('[Private Page]'); // @translate
        }

        return $sitePage->title();
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        $pages = $site->pages();

        // Handle an invalid page.
        if (!isset($pages[$data['id']])) {
            $fallback = new Fallback('page');
            return $fallback->toZend($data, $site);
        }
        $sitePage = $pages[$data['id']];

        // Handle a private page.
        if (!$sitePage->userIsAllowed('read')) {
            $fallback = new Fallback('page');
            return $fallback->toZend($data, $site);
        }

        return [
            'route' => 'site/page',
            'params' => [
                'site-slug' => $site->slug(),
                'page-slug' => $sitePage->slug(),
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'id' => $data['id'],
        ];
    }
}
