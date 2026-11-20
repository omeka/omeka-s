<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Adapter\VocabularyAdapter;
use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\VocabularyImport;
use Omeka\Stdlib\VocabularyReview;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var VocabularyReview
     */
    protected $vocabularyReview;

    /**
     * @var VocabularyImport
     */
    protected $vocabularyImport;

    /**
     * @var VocabularyAdapter
     */
    protected $vocabularyAdapter;

    /**
     * @param RdfImporter $rdfImporter
     * @param VocabularyReview $vocabularyReview
     * @param VocabularyImport $vocabularyImport
     * @param VocabularyAdapter $vocabularyAdapter
     */
    public function __construct(RdfImporter $rdfImporter, VocabularyReview $vocabularyReview,
        VocabularyImport $vocabularyImport, VocabularyAdapter $vocabularyAdapter
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->vocabularyReview = $vocabularyReview;
        $this->vocabularyImport = $vocabularyImport;
        $this->vocabularyAdapter = $vocabularyAdapter;
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
     * or a module handed them (VocabularyImport::start()). Show classes and
     * properties posts here to draw them again; Import posts to
     * finish-import, and Review changes to the vocabulary's Update.
     */
    public function importDetailsAction()
    {
        $details = $this->loadImportDetails();
        if (null === $details) {
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        if ($details['vocabulary']) {
            // Said before an import is refused for it.
            $this->messenger()->addWarning($this->getAlreadyHasMessage($details['vocabulary']));
        }
        return $this->getImportDetailsView($details);
    }

    /**
     * Import a vocabulary, last step: import details' Import.
     */
    public function finishImportAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import-details'], ['query' => $this->params()->fromQuery()]);
        }
        $details = $this->loadImportDetails();
        if (null === $details) {
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        $form = $details['form'];
        $vocabulary = $details['vocabulary'];
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            if ($vocabulary) {
                $this->messenger()->addWarning($this->getAlreadyHasMessage($vocabulary));
            }
            return $this->getImportDetailsView($details);
        }
        if ($vocabulary) {
            // Importing it would duplicate its terms.
            $this->messenger()->addError($this->getAlreadyHasMessage($vocabulary));
            return $this->getImportDetailsView($details);
        }
        try {
            $response = $this->rdfImporter->import('file', $form->getData()['vocabulary-info'], $details['options']);
        } catch (\Exception $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            // The refusal says why, so the list doesn't say it again.
            return $this->getImportDetailsView($details, false);
        }
        $this->vocabularyImport->end($details['token']);
        $this->messenger()->addSuccess($this->getImportedMessage());
        return $this->redirect()->toUrl($response->getContent()->url());
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
     * Update a vocabulary, first step: the newer file, chosen on this page or
     * staged by an import (import details' Review changes), which starts a
     * review.
     */
    public function updateAction()
    {
        if (null !== $this->params()->fromQuery('file_token')) {
            return $this->updateFromImport();
        }
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

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('form', $this->getForm(ConfirmForm::class));
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
        $choiceValues = VocabularyForm::getChoiceValues($importOptions);
        $view->setVariable('choices', $this->getReviewChoicesForm($choiceValues, $languages));
        $view->setVariable('choiceValues', $choiceValues);
        return $view;
    }

    /**
     * Update a vocabulary, last step: accept a review's changes.
     */
    public function acceptReviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $post = $this->params()->fromPost();
        $token = (string) ($post['review'] ?? '');
        $review = $this->getRequest()->isPost() ? $this->getReview($token, $vocabulary) : null;
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $chosen = VocabularyForm::getChosenImportOptions($post);
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        try {
            $this->vocabularyReview->accept($token, $this->vocabularyReview->getImportOptions($review, $chosen));
        } catch (ValidationException $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
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
        $post = $this->params()->fromPost();
        $token = (string) ($post['review'] ?? '');
        $review = $this->getRequest()->isPost() ? $this->getReview($token, $vocabulary) : null;
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        $this->vocabularyReview->cancel($token);
        return $this->redirect()->toUrl($vocabulary->url());
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
     * Load what import details' actions work from, or null when the import
     * has ended or its file can't be read, which it says.
     *
     * @return array|null
     *   - token, format: from the query
     *   - import: as VocabularyImport::get() gives it
     *   - file_details: what the file says, as
     *     RdfImporter::getVocabularyDetails() gives it
     *   - form: the import details form
     *   - namespace_uri: the form's
     *   - vocabulary: the one Omeka has under any form of it, or null
     *   - updatable: that vocabulary, where the admin may update it, or null
     *   - chosen: the import options chosen, as
     *     VocabularyForm::getChosenImportOptions() gives them
     *   - options: the import options to import and list with: the file,
     *     its format, and each choice, null where none was made
     */
    protected function loadImportDetails()
    {
        $token = (string) $this->params()->fromQuery('file_token');
        $format = (string) $this->params()->fromQuery('format');
        if (!isset(VocabularyForm::FORMATS[$format])) {
            $format = 'guess';
        }
        $import = $this->vocabularyImport->get($token);
        if (null === $import) {
            $this->vocabularyImport->end($token);
            $this->messenger()->addError('This import has ended. Choose the file or enter its URL again.'); // @translate
            return null;
        }
        try {
            $fileDetails = $this->rdfImporter->getVocabularyDetails('file', [
                'file' => $import['file'],
                'format' => $format,
                // As "en_US", which the importer matches as en-us.
                'lang' => $this->settings()->get('locale'),
            ]);
        } catch (\Exception $e) {
            $this->vocabularyImport->end($token);
            $this->messenger()->addErrors($this->getErrors($e));
            return null;
        }

        // The form: what the file says, then anything posted.
        $form = $this->getImportDetailsForm($fileDetails, $import);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
        }

        // A vocabulary Omeka has under any form of the namespace URI, and, on
        // the first visit, the choices to start with.
        $namespaceUri = trim((string) $form->get('vocabulary-info')->get('o:namespace_uri')->getValue());
        $vocabulary = $this->getVocabularyByNamespaceUri($namespaceUri);
        $updatable = $vocabulary && $vocabulary->userMayUpdate() ? $vocabulary : null;
        if (!$request->isPost()) {
            $form->setStartingChoices($this->getStartingChoices($updatable, $import));
        }

        // The import options chosen, none for a choice not made, as an
        // Another… field left empty.
        $chosen = VocabularyForm::getChosenImportOptions(array_map(fn ($element) => $element->getValue(), $form->get('vocabulary-advanced')->getElements()));
        // For importing and for the list.
        $options = ['file' => $import['file'], 'format' => $format];
        foreach (VocabularyImport::CHOICES as $choice) {
            $options[$choice] = $chosen[$choice] ?? null;
        }
        return [
            'token' => $token,
            'format' => $format,
            'import' => $import,
            'file_details' => $fileDetails,
            'form' => $form,
            'namespace_uri' => $namespaceUri,
            'vocabulary' => $vocabulary,
            'updatable' => $updatable,
            'chosen' => $chosen,
            'options' => $options,
        ];
    }

    /**
     * Get the import details page.
     *
     * @param array $details As loadImportDetails() gives them
     * @param bool $sayWhyNoList Whether to say why there's no list of classes
     *   and properties: not after a refused import, which said why itself
     * @return ViewModel
     */
    protected function getImportDetailsView(array $details, $sayWhyNoList = true)
    {
        // Import is the form's own action, so Enter in a field imports.
        $form = $details['form'];
        $form->setAttribute('action', $this->url()->fromRoute('admin/default', [
            'controller' => 'vocabulary',
            'action' => 'finish-import',
        ], [
            'query' => ['file_token' => $details['token'], 'format' => $details['format']],
        ]));

        $view = new ViewModel;
        $view->setTemplate('omeka/admin/vocabulary/import-details');
        $view->setVariable('form', $form);
        $view->setVariable('details', $details['file_details']);
        // For the URLs Show classes and properties and Review changes post to.
        $view->setVariable('token', $details['token']);
        $view->setVariable('format', $details['format']);
        // The vocabulary Review changes updates from the file.
        $view->setVariable('updatable', $details['updatable']);
        // None under a namespace URI Omeka has, since importing under it is
        // refused.
        $view->setVariable('members', $details['vocabulary'] ? null : $this->getMembersToImport($details['namespace_uri'], $details['options'], $sayWhyNoList));
        return $view;
    }

    /**
     * Get the import details form, filled in with what an import's file says
     * of its vocabulary, or what a module handed the import in its place.
     *
     * @param array $fileDetails As RdfImporter::getVocabularyDetails() gives
     *   it
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
     * Get the vocabulary Omeka has under any form of a namespace URI.
     *
     * @param string $namespaceUri
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyByNamespaceUri($namespaceUri)
    {
        if ('' === $namespaceUri) {
            return null;
        }
        $vocabulary = $this->vocabularyAdapter->findByNamespaceUri($namespaceUri);
        return $vocabulary ? $this->vocabularyAdapter->getRepresentation($vocabulary) : null;
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
     * Get the classes and properties importing adds: null without a namespace
     * URI, or where the file has none in it.
     *
     * @param string $namespaceUri
     * @param array $options The import options, as importing uses them
     * @param bool $sayWhy Whether to say why there are none
     * @return array|null Labels and comments by local name, by kind
     */
    protected function getMembersToImport($namespaceUri, array $options, $sayWhy)
    {
        if ('' === $namespaceUri) {
            return null;
        }
        try {
            $this->rdfImporter->checkHasMembers('file', $namespaceUri, $options);
            return $this->rdfImporter->getMembers('file', $namespaceUri, $options);
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
                htmlspecialchars($this->url()->fromRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import'])),
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
     * Get the request's vocabulary, refusing one the user may not change.
     *
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function getVocabularyToUpdate()
    {
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
        if ($vocabulary->isPermanent()) {
            throw new Exception\PermissionDeniedException('Cannot update a permanent vocabulary');
        }
        if (!$vocabulary->userIsAllowed('update')) {
            throw new Exception\PermissionDeniedException('User does not have permission to update this vocabulary');
        }
        return $vocabulary;
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
     * Update the vocabulary the URL names from the file an import staged, by
     * way of a review: import details' Review changes. Where the review can't
     * start, import details are drawn again, keeping what was entered.
     *
     * @return \Laminas\Http\Response|ViewModel
     */
    protected function updateFromImport()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import-details'], ['query' => $this->params()->fromQuery()]);
        }
        $details = $this->loadImportDetails();
        if (null === $details) {
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        // Only while the page's namespace URI is still that of the vocabulary
        // the URL names, and the admin may update it: once it's changed on the
        // page, or the vocabulary is gone or can't be changed, there's no
        // review to start.
        $updatable = $details['updatable'];
        if ($updatable && $updatable->id() === (int) $this->params('id')) {
            $form = $details['form'];
            // Only the CSRF field, as the review's own start checks the rest.
            $form->setValidationGroup(['csrf']);
            if (!$form->isValid()) {
                $this->messenger()->addFormErrors($form);
            } else {
                try {
                    // With import details' choices: a default chosen counts,
                    // and one not made is read as the vocabulary was.
                    $reviewUrl = $this->vocabularyReview->start($updatable->id(), $details['import']['file'], ['format' => $details['format']] + $details['chosen'], ['new_namespace_uri' => $details['namespace_uri']]);
                    // The review keeps its own copy, so the import is over.
                    $this->vocabularyImport->end($details['token']);
                    return $this->redirect()->toUrl($reviewUrl);
                } catch (\Exception $e) {
                    $this->messenger()->addErrors($this->getErrors($e));
                }
            }
        }
        if ($details['vocabulary']) {
            $this->messenger()->addWarning($this->getAlreadyHasMessage($details['vocabulary']));
        }
        return $this->getImportDetailsView($details);
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

    /**
     * Get the URL of a review's page, with the choices made on it.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param string $token
     * @param array $params
     * @return string
     */
    protected function getReviewUrl($vocabulary, $token, array $params)
    {
        $query = ['review' => $token];
        foreach (VocabularyForm::CHOICE_FIELDS as $key) {
            if (isset($params[$key]) && is_string($params[$key])) {
                $query[$key] = $params[$key];
            }
        }
        return $this->url()->fromRoute('admin/id', [
            'controller' => 'vocabulary',
            'id' => $vocabulary->id(),
            'action' => 'review',
        ], ['query' => $query]);
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
