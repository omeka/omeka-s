<?php
namespace Omeka\Controller\Admin;

use Laminas\Form\Form;
use Laminas\Mvc\Controller\AbstractActionController;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Exception\PermissionDeniedException;
use Omeka\Stdlib\OptionSidebar;

class OptionSidebarController extends AbstractActionController
{
    protected OptionSidebar $optionSidebar;

    public function __construct(OptionSidebar $optionSidebar)
    {
        $this->optionSidebar = $optionSidebar;
    }

    /**
     * Save or reset the arrangement of an "add" sidebar.
     *
     * Responds with the arrangements the sidebar can show, keyed by scope, as
     * JSON, so it can update without reloading the page.
     */
    public function saveAction()
    {
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        if (!$this->getRequest()->isPost()) {
            return $this->error(405, 'The arrangement must be POSTed.'); // @translate
        }
        $form = $this->getForm(Form::class, ['name' => 'option_sidebar']);
        $form->setData($this->params()->fromPost());
        if (!$form->isValid()) {
            return $this->error(403, 'The form has expired. Reload the page and try again.'); // @translate
        }

        $key = $this->params()->fromPost('sidebar');
        $level = $this->params()->fromPost('level');
        if (!is_string($key) || !$this->optionSidebar->has($key)
            || !in_array($level, $this->optionSidebar->getLevels($key), true)
        ) {
            return $this->error(400, 'Invalid sidebar or level.'); // @translate
        }

        $siteId = (int) $this->params()->fromPost('site_id') ?: null;
        if (!$siteId && $this->optionSidebar->isPerSite($key)) {
            return $this->error(400, 'This sidebar is arranged per site, so a site is required.'); // @translate
        }
        $site = null;
        if ($siteId) {
            try {
                $site = $this->api()->read('sites', $siteId)->getContent();
            } catch (NotFoundException | PermissionDeniedException $e) {
                return $this->error(404, 'Site not found.'); // @translate
            }
        }
        if (!$this->optionSidebar->canSave($key, $level, $site)) {
            $message = 'site' === $level
                ? 'You are not allowed to change the site default.' // @translate
                : 'You are not allowed to change the default for everyone.'; // @translate
            return $this->error(403, $message);
        }

        $arrangement = null;
        if ('1' !== $this->params()->fromPost('reset')) {
            $arrangement = [
                'pinned' => $this->params()->fromPost('pinned', []),
                'hidden' => $this->params()->fromPost('hidden', []),
            ];
        }
        $this->optionSidebar->save($key, $level, $arrangement, $siteId);

        $sharedLevel = $this->optionSidebar->getSharedLevel($key);
        $canSaveShared = $sharedLevel && $this->optionSidebar->canSave($key, $sharedLevel, $site);
        $response->setContent(json_encode($this->optionSidebar->getArrangementsByScope($key, $siteId, $canSaveShared)));
        return $response;
    }

    /**
     * Respond with an error status and a translated message, as JSON.
     */
    protected function error(int $statusCode, string $message)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setContent(json_encode(['error' => $this->translate($message)]));
        return $response;
    }
}
