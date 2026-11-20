<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
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

    /**
     * Show a vocabulary.
     */
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
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'file']);
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
     * Import a vocabulary, second step: its details, as the file gives them
     * or a module handed them (VocabularyImport::start()). The page's form
     * posts here, by three buttons: Import, Show classes and properties,
     * which only draws the page again, and, for a vocabulary Omeka has,
     * Review changes.
     */
    public function importDetailsAction()
    {
        // The import and what its file says, else back to Import.
        $fileToken = (string) $this->params()->fromQuery('file_token');
        $import = $this->vocabularyImport->get($fileToken);
        if (null === $import) {
            $this->vocabularyImport->end($fileToken);
            $this->messenger()->addError('This import has ended. Choose the file or enter its URL again.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => 'import'], true);
        }
        $format = (string) $this->params()->fromQuery('format');
        if (!isset(VocabularyForm::FORMATS[$format])) {
            $format = 'guess';
        }
        try {
            // In the installation's language, as "en_US", which the importer
            // matches as en-us.
            $fileDetails = $this->vocabularyImport->getDetails($import, $format, $this->settings()->get('locale'));
        } catch (\Exception $e) {
            $this->vocabularyImport->end($fileToken);
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toRoute(null, ['action' => 'import'], true);
        }

        // The form: what the file says, then anything posted.
        $form = $this->getImportDetailsForm($fileDetails, $import);
        $form->setAttribute('action', $this->url()->fromRoute(null, [], ['query' => ['file_token' => $fileToken, 'format' => $format]], true));
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
        }

        // The vocabulary Omeka has under any form of the namespace URI, and,
        // on the first visit, the choices to start with.
        $namespaceUri = trim((string) $form->get('vocabulary-info')->get('o:namespace_uri')->getValue());
        $existing = $this->vocabularyImport->findVocabulary($namespaceUri);
        $updatable = $existing && $existing->userMayUpdate() ? $existing : null;
        if (!$request->isPost()) {
            $form->setStartingChoices($this->getStartingChoices($updatable, $import));
        }

        // The import options chosen, none for a choice not made, as an
        // Another… field left empty.
        $chosen = VocabularyForm::getChosenImportOptions(array_map(fn ($element) => $element->getValue(), $form->get('vocabulary-advanced')->getElements()));
        // For importing and for the list: the format, and each choice, null
        // where none was made.
        $importOptions = ['format' => $format];
        foreach (VocabularyImport::CHOICES as $choice) {
            $importOptions[$choice] = $chosen[$choice] ?? null;
        }

        // The button clicked. Show classes and properties has no name, so its
        // post, like the first visit, only draws the page.
        $saidOmekaHasIt = false;
        $sayWhyNoList = true;
        if ($request->getPost('import')) {
            if (!$form->isValid()) {
                $this->messenger()->addFormErrors($form);
            } elseif ($existing) {
                // Importing it would duplicate its terms.
                $this->messenger()->addError($this->getAlreadyHasMessage($existing));
                $saidOmekaHasIt = true;
            } else {
                $vocabulary = $this->importVocabulary($import, $form->getData()['vocabulary-info'], $importOptions);
                if ($vocabulary) {
                    $this->messenger()->addSuccess($this->getImportedMessage());
                    return $this->redirect()->toUrl($vocabulary->url());
                }
                // The refusal says why, so the list doesn't say it again.
                $sayWhyNoList = false;
            }
        } elseif ($request->getPost('review_changes') && $updatable) {
            // With this page's choices: a default chosen counts, and one not
            // made is read as the vocabulary was.
            $reviewUrl = $this->startReviewFromImport($form, $import, $updatable, ['format' => $format] + $chosen, $namespaceUri);
            if ($reviewUrl) {
                return $this->redirect()->toUrl($reviewUrl);
            }
        }
        if ($existing && !$saidOmekaHasIt) {
            // Said before an import is refused for it.
            $this->messenger()->addWarning($this->getAlreadyHasMessage($existing));
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('fileDetails', $fileDetails);
        // The vocabulary Review changes updates from the file.
        $view->setVariable('updatable', $updatable);
        // None under a namespace URI Omeka has, since importing under it is
        // refused.
        $view->setVariable('members', $existing ? null : $this->getMembersToImport($import, $namespaceUri, $importOptions, $sayWhyNoList));
        return $view;
    }

    /**
     * Get the import details form, filled in with what an import's file says
     * of its vocabulary, or what a module handed the import in its place.
     *
     * @param array $fileDetails As VocabularyImport::getDetails() gives it
     * @param array $import As VocabularyImport::get() gives it
     * @return VocabularyForm
     */
    protected function getImportDetailsForm(array $fileDetails, array $import)
    {
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'import_details', 'languages' => $fileDetails['languages']]);
        $form->setData([
            'vocabulary-info' => array_replace([
                'o:label' => $fileDetails['label'],
                'o:comment' => $fileDetails['comment'],
                'o:namespace_uri' => $fileDetails['namespace_uri'],
                'o:prefix' => $fileDetails['prefix'],
            ], $import['vocabulary']),
            'vocabulary-advanced' => [
                // Nearest the installation's, else "", First available.
                'lang' => $fileDetails['lang'] ?? '',
                'comments' => 'comment',
            ],
        ]);
        return $form;
    }

    /**
     * Get the choices import details start with on the first visit: how the
     * file of a vocabulary Omeka has, which the admin may update, was last
     * read, so Review changes reads it the same way, then any a module handed
     * the import.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation|null $updatable
     * @param array $import As VocabularyImport::get() gives it
     * @return array
     */
    protected function getStartingChoices($updatable, array $import)
    {
        $startingChoices = [];
        $stored = $updatable ? $updatable->importOptions() : null;
        if (null !== $stored) {
            // A choice the file wasn't read with is set to null, which starts
            // its field on its default; unset, it would keep its preset, such
            // as the installation's language.
            foreach (VocabularyImport::CHOICES as $choice) {
                $startingChoices[$choice] = $stored[$choice] ?? null;
            }
        }
        return array_replace($startingChoices, $import['import_options']);
    }

    /**
     * Import details' Import: import the vocabulary from the import's file,
     * ending the import.
     *
     * @param array $import As VocabularyImport::get() gives it
     * @param array $info The vocabulary's fields, as entered
     * @param array $importOptions
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null The new
     *   vocabulary, or null where the import was refused, which it says
     */
    protected function importVocabulary(array $import, array $info, array $importOptions)
    {
        try {
            return $this->vocabularyImport->import($import, $info, $importOptions);
        } catch (\Exception $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            return null;
        }
    }

    /**
     * Import details' Review changes: update the vocabulary Omeka has from
     * the import's file, by way of a review, ending the import.
     *
     * @param VocabularyForm $form For its CSRF field
     * @param array $import As VocabularyImport::get() gives it
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param array $importOptions As VocabularyReview::start() takes them
     * @param string $namespaceUri The page's, which the vocabulary takes with
     *   the changes where it's another form of its own
     * @return string|null The review page's URL, or null where the review
     *   can't start, which it says
     */
    protected function startReviewFromImport(VocabularyForm $form, array $import, $vocabulary, array $importOptions, $namespaceUri)
    {
        // Only the CSRF field, as the review's own start checks the rest.
        $form->setValidationGroup(['csrf']);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return null;
        }
        try {
            $reviewUrl = $this->vocabularyReview->start($vocabulary->id(), $import['file'], $importOptions, ['new_namespace_uri' => $namespaceUri]);
        } catch (\Exception $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            return null;
        }
        // The review keeps its own copy, so the import is over.
        $this->vocabularyImport->end($import['file_token']);
        return $reviewUrl;
    }

    /**
     * Get the classes and properties importing adds: null without a namespace
     * URI, or where the file has none in it.
     *
     * @param array $import As VocabularyImport::get() gives it
     * @param string $namespaceUri
     * @param array $importOptions
     * @param bool $sayWhy Whether to say why there are none
     * @return array|null Labels and comments by local name, by kind
     */
    protected function getMembersToImport(array $import, $namespaceUri, array $importOptions, $sayWhy)
    {
        if ('' === $namespaceUri) {
            return null;
        }
        try {
            return $this->vocabularyImport->getMembers($import, $namespaceUri, $importOptions);
        } catch (\Exception $e) {
            if ($sayWhy) {
                $this->messenger()->addErrors($this->getErrors($e));
            }
            return null;
        }
    }

    /**
     * Get the message that a vocabulary was imported, with a link to import
     * another.
     *
     * @return Message
     */
    protected function getImportedMessage()
    {
        $message = new Message(
            'Vocabulary successfully imported. %s', // @translate
            sprintf(
                '<a href="%s">%s</a>',
                htmlspecialchars($this->url()->fromRoute(null, ['action' => 'import'], true)),
                $this->translate('Import another vocabulary?')
            )
        );
        $message->setEscapeHtml(false);
        return $message;
    }

    /**
     * Get the message that a vocabulary Omeka has uses an import's namespace
     * URI, in any form: Import's warning, and its refusal.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return Message
     */
    protected function getAlreadyHasMessage($vocabulary)
    {
        $message = new Message(
            $vocabulary->userMayUpdate()
                ? '%s already uses this namespace URI. To update it from the file, click Review changes. To import the file as a new vocabulary, enter its own namespace URI.' // @translate
                : '%s already uses this namespace URI. To import the file as a new vocabulary, enter its own namespace URI.', // @translate
            sprintf('<a href="%s">%s</a>', htmlspecialchars($vocabulary->url()), htmlspecialchars($vocabulary->label()))
        );
        $message->setEscapeHtml(false);
        return $message;
    }

    /**
     * Edit a vocabulary's label, comment, and namespace URI.
     */
    public function editAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary]);
        $form->setData([
            'vocabulary-info' => [
                'o:label' => $vocabulary->label(),
                'o:comment' => $vocabulary->comment(),
                'o:namespace_uri' => $vocabulary->namespaceUri(),
            ],
        ]);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $info = $form->getData()['vocabulary-info'];
                // Before the update, which changes what $vocabulary reads.
                $moved = $info['o:namespace_uri'] !== $vocabulary->namespaceUri();
                // The API refuses a namespace URI another vocabulary has, in
                // any form, and shows why on the form.
                $response = $this->api($form)->update('vocabularies', $this->params('id'), $info, [], ['isPartial' => true]);
                if ($response) {
                    if ($moved) {
                        $this->messenger()->addSuccess($this->getMovedMessage($vocabulary));
                    } else {
                        $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    }
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
     * Get the message that a vocabulary's namespace URI was changed, with a
     * link to update its classes and properties, which usually comes next.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return Message
     */
    protected function getMovedMessage($vocabulary)
    {
        $message = new Message(
            'Vocabulary successfully updated. %s', // @translate
            sprintf(
                '<a href="%s">%s</a>',
                htmlspecialchars($vocabulary->url('update')),
                $this->translate('Update its classes and properties?')
            )
        );
        $message->setEscapeHtml(false);
        return $message;
    }

    /**
     * Update a vocabulary, first step: the newer file, which starts a review.
     * Import details' Review changes starts one too.
     */
    public function updateAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary, 'mode' => 'file']);
        $format = $vocabulary->importOptions()['format'] ?? null;
        if (null !== $format) {
            $form->setData(['vocabulary-file' => ['format' => $format]]);
        }
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
     * Update a vocabulary, second step: the changes a review's file would
     * make, read with the page's choices.
     */
    public function reviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $importOptions = $this->vocabularyReview->getImportOptions($review, VocabularyForm::getChosenImportOptions($this->params()->fromQuery()));

        // Accept and Cancel post to their own actions with this page's query:
        // the review, and the choices the changes below were worked out with,
        // which Accept applies.
        $query = $this->params()->fromQuery();
        $form = $this->getForm(ConfirmForm::class);
        $form->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'accept-review'], ['query' => $query], true));

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('form', $form);
        $view->setVariable('cancelUrl', $this->url()->fromRoute(null, ['action' => 'cancel-review'], ['query' => $query], true));
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
        $view->setVariable('choices', $this->getReviewChoicesForm(VocabularyForm::getChoiceValues($importOptions), $languages));
        return $view;
    }

    /**
     * Update a vocabulary, last step: accept a review's changes, read with the
     * choices the review page showed, which its URL carries here.
     */
    public function acceptReviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        // Back to the review, as it was shown.
        $reviewUrl = $this->url()->fromRoute(null, ['action' => 'review'], ['query' => $this->params()->fromQuery()], true);
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->params()->fromPost());
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($reviewUrl);
        }
        $chosen = VocabularyForm::getChosenImportOptions($this->params()->fromQuery());
        try {
            $this->vocabularyReview->accept($token, $this->vocabularyReview->getImportOptions($review, $chosen));
        } catch (ValidationException $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toUrl($reviewUrl);
        }
        $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
        return $this->redirect()->toUrl($vocabulary->url());
    }

    /**
     * Cancel a review, changing nothing.
     */
    public function cancelReviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->params()->fromPost());
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toRoute(null, ['action' => 'review'], ['query' => $this->params()->fromQuery()], true);
        }
        $this->vocabularyReview->cancel($token);
        return $this->redirect()->toUrl($vocabulary->url());
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
     * Get the review page's form of choices, set to those the changes were
     * worked out with. It offers the file's languages, and the one chosen
     * even if the file dropped it.
     *
     * @param array $choiceValues As VocabularyForm::getChoiceValues() gives them
     * @param string[] $languages The file's languages
     * @return VocabularyForm
     */
    protected function getReviewChoicesForm(array $choiceValues, array $languages)
    {
        $lang = $choiceValues['lang'];
        if ('' !== $lang && !in_array($lang, $languages, true)) {
            $languages[] = $lang;
        }
        $choices = $this->getForm(VocabularyForm::class, ['mode' => 'review', 'languages' => $languages]);
        $choices->setData($choiceValues);
        return $choices;
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
     * Get the file a posted form chooses, saying why where there's none.
     *
     * @param VocabularyForm $form
     * @return array|null file (an upload's path) or url, and format
     */
    protected function getPostedFileSource(VocabularyForm $form)
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
        $data = $form->getData()['vocabulary-file'];
        // The form has refused a failed upload, so an empty temp name means
        // no file was chosen.
        $file = $data['file'] ?? [];
        $url = trim((string) ($data['url'] ?? ''));
        if ('upload' === $data['import_type'] && !empty($file['tmp_name'])) {
            return ['file' => $file['tmp_name'], 'format' => $data['format']];
        }
        if ('url' === $data['import_type'] && '' !== $url) {
            return ['url' => $url, 'format' => $data['format']];
        }
        $this->messenger()->addError('Choose a file or enter a URL.'); // @translate
        return null;
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
