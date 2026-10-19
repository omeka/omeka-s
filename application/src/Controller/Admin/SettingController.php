<?php
namespace Omeka\Controller\Admin;

use Omeka\Captcha\Manager as CaptchaManager;
use Omeka\Form\SettingForm;
use Omeka\Stdlib\Message;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SettingController extends AbstractActionController
{
    /**
     * @var CaptchaManager
     */
    protected $captchaManager;

    public function __construct(CaptchaManager $captchaManager)
    {
        $this->captchaManager = $captchaManager;
    }

    public function browseAction()
    {
        $form = $this->getForm(SettingForm::class);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                if ($data['index_fulltext_search']) {
                    $this->jobDispatcher()->dispatch('Omeka\Job\IndexFulltextSearch');
                }
                unset($data['index_fulltext_search']);
                unset($data['csrf']);
                foreach ($data as $id => $value) {
                    $this->settings()->set($id, $value);
                }
                $this->messenger()->addSuccess('Settings successfully updated'); // @translate
                $this->warnIfCaptchaInactive($data['captcha'] ?? null);
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Warn when a CAPTCHA provider is selected but can't be used.
     *
     * Public forms are then unprotected, which the admin may not expect after
     * selecting a provider.
     */
    protected function warnIfCaptchaInactive(?string $captcha)
    {
        if (!$captcha) {
            return;
        }
        if (!$this->captchaManager->has($captcha)) {
            $this->messenger()->addWarning('CAPTCHA is off because the selected provider is unavailable.'); // @translate
            return;
        }
        $provider = $this->captchaManager->get($captcha);
        if (!$provider->isConfigured()) {
            $this->messenger()->addWarning(new Message(
                'CAPTCHA is off because %s is missing required settings.', // @translate
                $this->translate($provider->getLabel())
            ));
        }
    }
}
