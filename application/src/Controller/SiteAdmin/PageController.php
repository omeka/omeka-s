<?php
namespace Omeka\Controller\SiteAdmin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\SitePageForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    public function editAction()
    {
        $site = $this->currentSite();
        $page = $this->api()->read('site_pages', [
            'slug' => $this->params('page-slug'),
            'site' => $site->id(),
        ])->getContent();

        $form = $this->getForm(SitePageForm::class);
        $form->setData($page->jsonSerialize());

        if ($this->getRequest()->isPost()) {
            $post = $this->params()->fromPost();
            $form->setData($post);
            if ($form->isValid()) {
                $response = $this->api($form)->update('site_pages', $page->id(), $post);
                if ($response) {
                    $this->messenger()->addSuccess('Page successfully updated'); // @translate
                    // Explicitly re-read the site URL instead of using
                    // refresh() so we catch updates to the slug
                    return $this->redirect()->toUrl($page->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('page', $page);
        $view->setVariable('form', $form);
        return $view;
    }

    public function indexAction()
    {
        /** @var \Omeka\Api\Representation\SiteRepresentation $site */
        $site = $this->currentSite();
        $indents = [];
        $navSorting = false;

        // Manage the default special sort (navigation).
        $sortBy = $this->params()->fromQuery('sort_by', 'nav');

        if (empty($sortBy) || $sortBy === 'nav') {
            $navSorting = true;
            $pages = array_merge($site->linkedPages(), $site->notlinkedPages());
            if ($this->params()->fromQuery('sort_order') === 'desc') {
                $pages = array_reverse($pages, true);
            }
        } else {
            // No pagination for pages.
            $this->setBrowseDefaults('modified', 'desc', null);
            $query = $this->params()->fromQuery();
            $query['site_id'] = $site->id();
            $response = $this->api()->search('site_pages', $query);
            $this->paginator($response->getTotalResults());
            $pages = $response->getContent();
        }

        $iterate = function ($linksIn, $depth = 0) use (&$iterate, &$indents) {
            foreach ($linksIn as $key => $data) {
                if ('page' === $data['type']) {
                    $indents[$data['data']['id']] = $depth;
                }
                if (isset($data['links'])) {
                    $iterate($data['links'], $depth + 1);
                }
            }
        };
        if ($navSorting) {
            $iterate($site->navigation());
        }

        return new ViewModel([
            'site' => $site,
            'pages' => $pages,
            'indents' => $indents,
            'sortBy' => $sortBy,
        ]);
    }

    public function deleteConfirmAction()
    {
        $site = $this->currentSite();
        $page = $this->api()->read('site_pages', [
            'slug' => $this->params('page-slug'),
            'site' => $site->id(),
        ])->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('partialPath', 'omeka/site-admin/page/show-details');
        $view->setVariable('resourceLabel', 'page'); // @translate
        $view->setVariable('resource', $page);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $site = $this->currentSite();
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('site_pages', [
                    'slug' => $this->params('page-slug'),
                    'site' => $site->id(),
                ]);
                if ($response) {
                    $this->messenger()->addSuccess('Page successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        return $this->redirect()->toRoute(
            'admin/site/slug/page',
            ['action' => 'index'],
            true
        );
    }

    public function blockAction()
    {
        $site = $this->currentSite();
        $page = $this->api()->read('site_pages', [
            'slug' => $this->params('page-slug'),
            'site' => $site->id(),
        ])->getContent();

        $content = $this->viewHelpers()->get('blockLayout')->form(
            $this->params()->fromPost('layout'), $site, $page
        );

        $response = $this->getResponse();
        $response->setContent($content);
        return $response;
    }

    public function attachmentItemOptionsAction()
    {
        $attachedItem = null;
        $attachedMedia = null;

        $itemId = $this->params()->fromPost('itemId');
        if ($itemId) {
            $attachedItem = $this->api()->read('items', $itemId)->getContent();
        }
        $mediaId = $this->params()->fromPost('mediaId');
        if ($mediaId) {
            $attachedMedia = $this->api()->read('media', $mediaId)->getContent();
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('attachedItem', $attachedItem);
        $view->setVariable('attachedMedia', $attachedMedia);
        $view->setVariable('site', $this->currentSite());
        return $view;
    }
}
