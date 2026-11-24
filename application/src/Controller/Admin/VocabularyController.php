<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyFileForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyImportOptionsForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\VocabularyImport;
use Omeka\Stdlib\VocabularyReview;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    /**
     * @var VocabularyImport
     */
    protected $vocabularyImport;

    /**
     * @var VocabularyReview
     */
    protected $vocabularyReview;

    /**
     * @param VocabularyImport $vocabularyImport
     * @param VocabularyReview $vocabularyReview
     */
    public function __construct(VocabularyImport $vocabularyImport, VocabularyReview $vocabularyReview)
    {
        $this->vocabularyImport = $vocabularyImport;
        $this->vocabularyReview = $vocabularyReview;
    }

    public function browseAction()
    {
        $this->browse()->setDefaults('vocabularies');
        $response = $this->api()->search('vocabularies', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('vocabularies', $response->getContent());
        return $view;
    }

    public function showAction()
    {
        $view = new ViewModel;
        $view->setVariable('vocabulary', $this->api()->read('vocabularies', $this->params('id'))->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('vocabularies', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('vocabularies', $this->params('id'));
        $vocabulary = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $vocabulary);
        $view->setVariable('resourceLabel', 'vocabulary'); // @translate
        $view->setVariable('partialPath', 'omeka/admin/vocabulary/show-details');
        $view->setVariable('deleting', true);
        return $view;
    }

    /**
     * Import a vocabulary, first step: start the import of the chosen file,
     * then go on to its details.
     */
    public function importAction()
    {
        $form = $this->getForm(VocabularyFileForm::class);
        $source = $this->getRequest()->isPost() ? $this->getPostedFileSource($form) : null;
        if ($source) {
            $importOptions = ['format' => $source['format']];
            try {
                $importUrl = isset($source['file'])
                    ? $this->vocabularyImport->start($source['file'], [], $importOptions)
                    : $this->vocabularyImport->startFromUrl($source['url'], [], $importOptions);
                return $this->redirect()->toUrl($importUrl);
            } catch (\Exception $e) {
                $this->messenger()->addErrors($this->getErrors($e));
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Import a vocabulary, second and last step: its details, as the file
     * gives them or a module handed them (VocabularyImport::start()).
     */
    public function importDetailsAction()
    {
        $token = (string) $this->params()->fromQuery('token');
        $import = $this->vocabularyImport->get($token);
        if (null === $import) {
            $this->messenger()->addError('This import has ended. Choose the file or enter its URL again.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => 'import'], true);
        }
        try {
            // In the installation's language, as "en_US", which the importer
            // matches as en-us.
            $fileDetails = $this->vocabularyImport->getFileDetails($import, $this->settings()->get('locale'));
        } catch (\Exception $e) {
            // The file was checked at the start, but it's read again on each
            // request.
            $this->vocabularyImport->cancel($token);
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toRoute(null, ['action' => 'import'], true);
        }

        $form = $this->getForm(VocabularyForm::class);
        $form->setAttribute('action', $this->url()->fromRoute(null, [], ['query' => ['token' => $token]], true));
        $optionsForm = $this->getForm(VocabularyImportOptionsForm::class, ['languages' => $fileDetails['languages']]);
        $request = $this->getRequest();
        if ($request->isPost()) {
            // One post fills both, as their fields' names don't overlap.
            $post = $request->getPost()->toArray();
            $form->setData($post);
            $optionsForm->setData($post);
        } else {
            $form->setData($this->vocabularyImport->getStartingFields($import, $fileDetails));
        }

        $namespaceUri = $form->getNamespaceUri();
        $existing = $this->vocabularyImport->findVocabulary($namespaceUri);
        $updatable = $existing && $existing->userMayUpdate() ? $existing : null;
        if (!$request->isPost()) {
            $optionsForm->setChoices($this->vocabularyImport->getStartingChoices($import, $fileDetails, $updatable));
        }

        $chosen = $optionsForm->getChosen();
        $importOptions = $this->vocabularyImport->getImportOptions($import, $chosen);

        // Omeka's vocabulary under the namespace URI, exactly or in another
        // form, such as https for http. Only an exact match can't be imported
        // again. Namespace URIs are opaque, so another form is only a likely
        // duplicate: it's pointed out, and the user decides. A change only in
        // capitals isn't another form to the API.
        $otherForm = $existing && strtolower($existing->namespaceUri()) !== strtolower($namespaceUri);
        $existingMessage = null;
        if ($existing) {
            if ($otherForm && $existing->userMayUpdate()) {
                $text = '%1$s uses another form of this namespace URI, %2$s, so the file may hold the same vocabulary. To update it from the file and move it to this namespace URI, click Review changes. To import the file as a new vocabulary anyway, give it a prefix of its own and click Import.'; // @translate
            } elseif ($otherForm) {
                $text = '%1$s uses another form of this namespace URI, %2$s, so the file may hold the same vocabulary. To import the file as a new vocabulary anyway, give it a prefix of its own and click Import.'; // @translate
            } elseif ($existing->userMayUpdate()) {
                $text = '%s already uses this namespace URI. To update it from the file, click Review changes. To import the file as a new vocabulary, enter its own namespace URI.'; // @translate
            } else {
                $text = '%s already uses this namespace URI. To import the file as a new vocabulary, enter its own namespace URI.'; // @translate
            }
            $existingMessage = new Message(
                $text,
                sprintf('<a href="%s">%s</a>', htmlspecialchars($existing->url()), htmlspecialchars($existing->label())),
                htmlspecialchars($existing->namespaceUri())
            );
            $existingMessage->setEscapeHtml(false);
        }

        // What's known of the namespace URI, worked out on every request,
        // whatever was clicked: the classes and properties importing would
        // add, or why there are none. Not under an exact match, since
        // importing it again would duplicate its terms.
        $members = null;
        $membersFailure = null;
        if ((!$existing || $otherForm) && '' !== $namespaceUri) {
            try {
                $members = $this->vocabularyImport->getMembers($import, $namespaceUri, $importOptions);
            } catch (\Exception $e) {
                // Said after the buttons, so a refused Import's form errors
                // don't say it again under the field's label.
                $membersFailure = $e;
            }
        }

        // Show classes and properties has no name, so its post only draws the
        // page.
        if ($request->getPost('import')) {
            if (!$form->isValid()) {
                $this->messenger()->addFormErrors($form);
            } elseif (null !== $members) {
                // Not under an exact match, or when the classes and
                // properties couldn't be read, both said below.
                try {
                    $vocabulary = $this->vocabularyImport->import($import['token'], $form->getData(), $importOptions);
                    $message = new Message(
                        'Vocabulary successfully imported. %s', // @translate
                        sprintf(
                            '<a href="%s">%s</a>',
                            htmlspecialchars($this->url()->fromRoute(null, ['action' => 'import'], true)),
                            $this->translate('Import another vocabulary?')
                        )
                    );
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toUrl($vocabulary->url());
                } catch (\Exception $e) {
                    // The import is still open to try again.
                    $this->sayFailure($e, $form, $optionsForm);
                }
            }
        } elseif ($request->getPost('review_changes')) {
            if ($updatable) {
                // With this page's choices: a default chosen counts, and one
                // not made, an Another… field left blank, is read as the
                // vocabulary was.
                $reviewUrl = $this->startReviewFromImport($form, $import, $updatable, ['format' => $importOptions['format']] + $chosen, $namespaceUri);
                if ($reviewUrl) {
                    return $this->redirect()->toUrl($reviewUrl);
                }
            } else {
                // The namespace URI was changed after the page offered Review
                // changes.
                $this->messenger()->addError('No vocabulary you may update uses this namespace URI, so there are no changes to review.'); // @translate
            }
        }
        // Said after the buttons, so a successful Import or Review changes
        // doesn't carry it to the next page. An exact match refuses Import.
        if ($existingMessage) {
            if ($request->getPost('import') && !$otherForm) {
                $this->messenger()->addError($existingMessage);
            } else {
                $this->messenger()->addWarning($existingMessage);
            }
        }
        if ($membersFailure) {
            $this->sayFailure($membersFailure, $form, $optionsForm);
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('optionsForm', $optionsForm);
        $view->setVariable('fileDetails', $fileDetails);
        // The vocabulary Review changes updates from the file.
        $view->setVariable('updatable', $updatable);
        $view->setVariable('members', $members);
        return $view;
    }

    /**
     * Edit a vocabulary's label, comment, and namespace URI.
     */
    public function editAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary]);
        $form->setData([
            'o:label' => $vocabulary->label(),
            'o:comment' => $vocabulary->comment(),
            'o:namespace_uri' => $vocabulary->namespaceUri(),
        ]);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                // The API refuses a namespace URI another vocabulary has, and
                // shows why on the form.
                $response = $this->api($form)->update('vocabularies', $this->params('id'), $form->getData(), [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Update a vocabulary, first step: the newer file, which starts a review.
     */
    public function updateAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyFileForm::class, ['vocabulary' => $vocabulary]);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $source = $this->getRequest()->isPost() ? $this->getPostedFileSource($form) : null;
        if ($source) {
            $importOptions = ['format' => $source['format']];
            try {
                $reviewUrl = isset($source['file'])
                    ? $this->vocabularyReview->start($vocabulary->id(), $source['file'], $importOptions)
                    : $this->vocabularyReview->startFromUrl($vocabulary->id(), $source['url'], $importOptions);
                return $this->redirect()->toUrl($reviewUrl);
            } catch (\Exception $e) {
                $this->messenger()->addErrors($this->getErrors($e));
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Update a vocabulary, second and last step: the changes a review's file
     * would make, read with the page's choices.
     */
    public function reviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $token = (string) $this->params()->fromQuery('token');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $importOptions = $this->vocabularyReview->getImportOptions($review, VocabularyImportOptionsForm::getChosenImportOptions($this->params()->fromQuery()));

        // Accept posts back here with this page's query: the review and its
        // choices.
        $form = $this->getForm(ConfirmForm::class);
        $form->setAttribute('action', $this->url()->fromRoute(null, [], ['query' => $this->params()->fromQuery()], true));
        $request = $this->getRequest();
        if ($request->getPost('accept')) {
            $form->setData($request->getPost()->toArray());
            if (!$form->isValid()) {
                $this->messenger()->addFormErrors($form);
            } else {
                try {
                    $this->vocabularyReview->accept($token, $importOptions);
                    $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    return $this->redirect()->toUrl($vocabulary->url());
                } catch (ValidationException $e) {
                    // The review is still open to try again.
                    $this->messenger()->addErrors($this->getErrors($e));
                }
            }
        }

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('form', $form);
        $languages = [];
        try {
            $view->setVariable('diff', $this->vocabularyReview->getDiff($review, $importOptions));
            $view->setVariable('notInFile', $this->vocabularyReview->getMembersNotInFile($review, $importOptions));
            $languages = $this->vocabularyReview->getLanguages($review, $importOptions);
        } catch (\Exception $e) {
            // The choices may make the file unreadable; keep them to change.
            $this->messenger()->addErrors($this->getErrors($e));
            $view->setVariable('diff', null);
            $view->setVariable('notInFile', null);
        }
        $choices = $this->getForm(VocabularyImportOptionsForm::class, ['languages' => $languages]);
        $choices->setChoices($importOptions);
        $view->setVariable('choices', $choices);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
            if ($vocabulary->isPermanent()) {
                throw new Exception\PermissionDeniedException('Cannot delete a permanent vocabulary');
            }
            if (!$vocabulary->userIsAllowed('delete')) {
                throw new Exception\PermissionDeniedException('User does not have permission to delete this vocabulary');
            }
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('vocabularies', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Vocabulary successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
    }

    public function propertiesAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $this->browse()->setDefaults('properties');
        $this->getRequest()->getQuery()->set('vocabulary_id', $this->params('id'));
        $propResponse = $this->api()->search('properties', $this->params()->fromQuery());
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));
        $this->paginator($propResponse->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('properties', $propResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }

    public function classesAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $this->browse()->setDefaults('resource_classes');
        $this->getRequest()->getQuery()->set('vocabulary_id', $this->params('id'));
        $classResponse = $this->api()->search('resource_classes', $this->params()->fromQuery());
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));
        $this->paginator($classResponse->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('resourceClasses', $classResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }

    /**
     * Say why reading import details' file or importing it failed. A
     * refusal's reasons also go on the fields they name, as Edit's do.
     *
     * @param \Exception $e
     * @param VocabularyForm $form The vocabulary's fields
     * @param VocabularyImportOptionsForm $optionsForm The choices' fields
     */
    protected function sayFailure(\Exception $e, VocabularyForm $form, VocabularyImportOptionsForm $optionsForm)
    {
        if (!$e instanceof ValidationException) {
            $this->messenger()->addErrors($this->getErrors($e));
            return;
        }
        if ('' !== (string) $e->getMessage()) {
            $this->messenger()->addError($e->getMessage());
        }
        $this->api($form)->handleValidationException($e);
        // The choices' reasons, such as a label property that can't be read,
        // on their fields too.
        $choiceMessages = [];
        foreach ($e->getErrorStore()->getErrors() as $key => $messages) {
            if (!$optionsForm->has($key)) {
                continue;
            }
            foreach ($messages as $message) {
                if (!is_array($message)) {
                    $choiceMessages[$key][] = $this->translate($message);
                }
            }
        }
        $optionsForm->setMessages($choiceMessages);
    }

    /**
     * Import details' Review changes: update the vocabulary Omeka has from
     * the import's file, by way of a review, ending the import.
     *
     * @param VocabularyForm $form For its CSRF field
     * @param array $import As VocabularyImport::get() gives it
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param array $importOptions As VocabularyReview::start() takes them
     * @param string $namespaceUri The page's. Where it's another form of the
     *   vocabulary's, such as https for http, accepting the review changes the
     *   vocabulary's to it.
     * @return string|null The review page's URL, or null where the review
     *   can't start, which it says
     */
    protected function startReviewFromImport(VocabularyForm $form, array $import, $vocabulary, array $importOptions, $namespaceUri)
    {
        // Only the CSRF field: a review uses only the namespace URI, which
        // its start checks.
        $form->setValidationGroup([$form->getName() . '_csrf']);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return null;
        }
        try {
            $reviewUrl = $this->vocabularyReview->start($vocabulary->id(), $import['files']['source'], $importOptions, ['new_namespace_uri' => $namespaceUri]);
        } catch (\Exception $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            return null;
        }
        // The review keeps its own copy, so the import is over.
        $this->vocabularyImport->cancel($import['token']);
        return $reviewUrl;
    }

    /**
     * Get a review of the vocabulary, or say it has ended.
     *
     * @param string $token
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return array|null
     */
    protected function getReview($token, $vocabulary)
    {
        $review = $this->vocabularyReview->get($token);
        if (!$review || $review['vocabulary_id'] !== $vocabulary->id()) {
            $this->messenger()->addError('This review has ended.'); // @translate
            return null;
        }
        return $review;
    }

    /**
     * Get the file a posted form chooses, saying why where there's none.
     *
     * @param VocabularyFileForm $form
     * @return array|null file (an upload's path) or url, and format
     */
    protected function getPostedFileSource(VocabularyFileForm $form)
    {
        $request = $this->getRequest();
        $form->setData(array_merge_recursive(
            $request->getPost()->toArray(),
            $request->getFiles()->toArray()
        ));
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return null;
        }
        $source = $form->getFileSource();
        if (!$source) {
            $this->messenger()->addError('Choose a file or enter a URL.'); // @translate
        }
        return $source;
    }

    /**
     * Get the request's vocabulary, refusing one the user may not change.
     *
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function getVocabularyToUpdate()
    {
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
        if (!$vocabulary->userMayUpdate()) {
            throw new Exception\PermissionDeniedException('User does not have permission to update this vocabulary');
        }
        return $vocabulary;
    }

    /**
     * Get an exception's messages and its error store's, else that something
     * went wrong.
     *
     * @param \Exception $e
     * @return array
     */
    protected function getErrors(\Exception $e)
    {
        // An API refusal has no message of its own, only its reasons.
        $errors = '' === (string) $e->getMessage() ? [] : [$e->getMessage()];
        if ($e instanceof ValidationException && $e->getErrorStore()->getErrors()) {
            $errors[] = $e->getErrorStore()->getErrors();
        }
        return $errors ?: ['Something went wrong']; // @translate
    }
}
