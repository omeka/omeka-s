<?php
namespace Collecting\Api\Representation;

use Collecting\Form\Element;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Module\Manager as ModuleManager;
use Laminas\Form\Form;
use Laminas\Http\PhpEnvironment\RemoteAddress;

class CollectingFormRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var Form
     */
    protected $form;

    public function getControllerName()
    {
        return 'collecting';
    }

    public function getJsonLdType()
    {
        return 'o-module-collecting:Form';
    }

    public function getJsonLd()
    {
        if ($site = $this->site()) {
            $site = $site->getReference();
        }
        if ($itemSet = $this->itemSet()) {
            $itemSet = $itemSet->getReference();
        }
        return [
            'o-module-collecting:label' => $this->label(),
            'o-module-collecting:anon_type' => $this->anonType(),
            'o-module-collecting:success_text' => $this->successText(),
            'o-module-collecting:email_text' => $this->emailText(),
            'o-module-collecting:default_site_assign' => $this->defaultSiteAssign(),
            'o:site' => $site,
            'o:item_set' => $itemSet,
            'o-module-collecting:prompt' => $this->prompts(),
        ];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/slug/collecting/id',
            [
                'site-slug' => $this->site()->slug(),
                'controller' => $this->getControllerName(),
                'action' => $action,
                'form-id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function label()
    {
        return $this->resource->getLabel();
    }

    public function anonType()
    {
        return $this->resource->getAnonType();
    }

    public function itemSet()
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation($this->resource->getItemSet());
    }

    public function successText()
    {
        return $this->resource->getSuccessText();
    }

    public function emailText()
    {
        return $this->resource->getEmailText();
    }

    public function defaultSiteAssign()
    {
        return $this->resource->getDefaultSiteAssign();
    }

    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation($this->resource->getSite());
    }

    public function prompts()
    {
        $prompts = [];
        foreach ($this->resource->getPrompts() as $prompt) {
            $prompts[] = new CollectingPromptRepresentation($prompt, $this->getServiceLocator());
        }
        return $prompts;
    }

    /**
     * Get the object used to validate and render this form.
     *
     * @return Form
     */
    public function getForm()
    {
        if ($this->form) {
            return $this->form; // build the form object only once
        }
        $url = $this->getViewHelper('Url');
        $collecting = $this->getViewHelper('collecting');
        $mediaTypes = $this->getServiceLocator()->get('Collecting\MediaTypeManager');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $modules = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $user = $auth->getIdentity(); // returns a User entity or null

        $customVocabModule = $modules->getModule('CustomVocab');
        $customVocabActive = $customVocabModule && ModuleManager::STATE_ACTIVE === $customVocabModule->getState();

        $form = new Form(sprintf('collecting_form_%s', $this->id()));
        $this->form = $form; // cache the form
        $form->setAttribute('action', $url('site/collecting', [
            'form-id' => $this->id(),
            'action' => 'submit',
        ], true));

        $hasUserEmailPrompt = false;
        foreach ($this->prompts() as $prompt) {
            $name = sprintf('prompt_%s', $prompt->id());
            switch ($prompt->type()) {
                // Note that there's no break here. When building the form we
                // handle property, input, and user prompts the same.
                case 'property':
                case 'input':
                case 'user_private':
                case 'user_public':
                    switch ($prompt->inputType()) {
                        case 'text':
                            $element = new Element\PromptText($name);
                            break;
                        case 'textarea':
                            $element = new Element\PromptTextarea($name);
                            break;
                        case 'select':
                            $selectOptions = explode("\n", $prompt->selectOptions());
                            $element = new Element\PromptSelect($name);
                            $element->setEmptyOption('Please choose one...') // @translate
                                ->setValueOptions(array_combine($selectOptions, $selectOptions));
                            break;
                        case 'item':
                            parse_str(ltrim($prompt->resourceQuery(), '?'), $resourceQuery);
                            $element = new Element\PromptItem($name);
                            $element->setApiManager($api);
                            $element->setEmptyOption('Please choose one...') // @translate
                                ->setResourceValueOptions('items', function ($item) {
                                    return sprintf('#%s: %s', $item->id(), mb_substr($item->displayTitle(), 0, 80));
                                }, $resourceQuery);
                            break;
                        case 'url':
                            $element = new Element\PromptUrl($name);
                            break;
                        case 'custom_vocab':
                            if (!$customVocabActive) {
                                continue 3; // CustomVocab module must be active
                            }
                            try {
                                $customVocab = $api->read('custom_vocabs', $prompt->customVocab())->getContent();
                            } catch (NotFoundException $e) {
                                continue 3; // The custom vocab does not exist
                            }
                            $customVocabTerms = $customVocab->terms();
                            if (!$customVocabTerms) {
                                continue 3; // URIs and Items vocab types not implemented
                            }
                            $element = new Element\PromptSelect($name);
                            $element->setEmptyOption('Please choose one...') // @translate
                                ->setValueOptions(array_combine($customVocabTerms, $customVocabTerms));
                            break;
                        case 'numeric:timestamp':
                            if (!$collecting->inputTypeIsAvailable('numeric:timestamp')) {
                                continue 3;
                            }
                            $element = new Element\PromptNumericTimestamp($name);
                            break;
                        case 'numeric:interval':
                            if (!$collecting->inputTypeIsAvailable('numeric:interval')) {
                                continue 3;
                            }
                            $element = new Element\PromptNumericInterval($name);
                            break;
                        case 'numeric:duration':
                            if (!$collecting->inputTypeIsAvailable('numeric:duration')) {
                                continue 3;
                            }
                            $element = new Element\PromptNumericDuration($name);
                            break;
                        case 'numeric:integer':
                            if (!$collecting->inputTypeIsAvailable('numeric:integer')) {
                                continue 3;
                            }
                            $element = new Element\PromptNumericInteger($name);
                            break;
                        default:
                            // Invalid prompt input type. Do nothing.
                            continue 3;
                    }
                    $label = ($prompt->property() && !$prompt->text())
                        ? $prompt->property()->label()
                        : $prompt->text();
                    $element->setLabel($label)
                        ->setIsRequired($prompt->required());
                    $form->add($element);
                    break;
                case 'user_name':
                    $element = new Element\PromptText($name);
                    $element->setLabel($prompt->text())
                        ->setIsRequired($prompt->required());
                    if ($user) {
                        $element->setValue($user->getName());
                    }
                    $form->add($element);
                    break;
                case 'user_email':
                    $hasUserEmailPrompt = true;
                    $element = new Element\PromptEmail($name);
                    $element->setLabel($prompt->text())
                        ->setIsRequired($prompt->required());
                    if ($user) {
                        $element->setValue($user->getEmail());
                    }
                    $form->add($element);
                    break;
                case 'html':
                    $element = new Element\PromptHtml($name);
                    $element->setHtml($prompt->text());
                    $form->add($element);
                    break;
                case 'media':
                    $mediaTypes->get($prompt->mediaType())->form($form, $prompt, $name);
                    break;
                default:
                    // Invalid prompt type. Do nothing.
                    continue 2;
            }
        }

        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $translator = $this->getServiceLocator()->get('MvcTranslator');

        if ('user' === $this->anonType()) {
            $form->add([
                'type' => 'checkbox',
                'name' => sprintf('anon_%s', $this->id()),
                'options' => [
                    'label' => 'I want to submit anonymously', // @translate
                ],
            ]);
        }

        if ($hasUserEmailPrompt) {
            $form->add([
                'type' => 'checkbox',
                'name' => sprintf('email_send_%s', $this->id()),
                'options' => [
                    'label' => 'Email me my submission', // @translate
                ],
            ]);
        }

        // Add the terms of service if provided in site settings.
        $tos = $siteSettings->get('collecting_tos');
        $tosUrl = $siteSettings->get('collecting_tos_url');
        if ($tos || $tosUrl) {
            $tosUrl = $tosUrl ?: $url('site/collecting', [
                'form-id' => $this->id(),
                'action' => 'tos',
            ], true);
            $form->add([
                'type' => 'checkbox',
                'name' => sprintf('tos_accept_%s', $this->id()),
                'attributes' => [
                    'required' => true,
                ],
                'options' => [
                    'label' => sprintf(
                        $translator->translate('I accept the %s'),
                        sprintf(
                            '<a href="' . $tosUrl . '" target="_blank" style="text-decoration: underline;">%s</a>',
                            $translator->translate('Terms of Service')
                        )
                    ),
                    'label_options' => [
                        'disable_html_escape' => true,
                    ],
                    'use_hidden_element' => false,
                ],
            ]);
        }

        // Add reCAPTCHA protection if keys are provided in global settings.
        $siteKey = $settings->get('recaptcha_site_key');
        $secretKey = $settings->get('recaptcha_secret_key');
        if ($siteKey && $secretKey) {
            $element = $this->getServiceLocator()
                ->get('FormElementManager')
                ->get('Omeka\Form\Element\Recaptcha', [
                    'site_key' => $siteKey,
                    'secret_key' => $secretKey,
                    'remote_ip' => (new RemoteAddress)->getIpAddress(),
                ]);
            $form->add($element);
        }

        $form->add([
            'type' => 'csrf',
            'name' => sprintf('csrf_%s', $this->id()),
            'options' => [
                'csrf_options' => ['timeout' => 3600],
            ],
        ]);
        $form->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Submit',
            ],
        ]);
        return $form;
    }
}
