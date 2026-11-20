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
     * Import a vocabulary, second step: its fields and import options, filled
     * in from the file the query's file_token names, or from what a module
     * handed the import (VocabularyImport::start()).
     */
    public function importDetailsAction()
    {
        // The import and what its file says, else back to Import.
        $token = (string) $this->params()->fromQuery('file_token');
        $format = (string) $this->params()->fromQuery('format');
        if (!isset(VocabularyForm::FORMATS[$format])) {
            $format = 'guess';
        }
        $import = $this->vocabularyImport->get($token);
        if (null === $import) {
            $this->vocabularyImport->end($token);
            $this->messenger()->addError('This import has ended. Choose the file or enter its URL again.'); // @translate
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        try {
            $details = $this->getImportDetails($import['file'], $format);
        } catch (\Exception $e) {
            $this->vocabularyImport->end($token);
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }

        // The form: what the file says, then what was posted.
        $form = $this->getImportDetailsForm($details, $import);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
        }

        // A vocabulary Omeka has under any form of the namespace URI, and, on
        // the first visit, the choices to start with.
        $namespaceUri = trim((string) $form->get('vocabulary-info')->get('o:namespace_uri')->getValue());
        $other = $this->getVocabularyByNamespaceUri($namespaceUri);
        $updatable = $other && $other->userMayUpdate() ? $other : null;
        if (!$request->isPost()) {
            $form->setStartingChoices($this->getStartingChoices($updatable, $import));
        }
        // The import options chosen, none for a choice not made, as an
        // Another… field left empty.
        $chosen = VocabularyForm::getChosenImportOptions(array_map(fn ($element) => $element->getValue(), $form->get('vocabulary-advanced')->getElements()));
        $options = $this->getImportOptions($import, $format, $chosen);

        // The button clicked; none on a first visit. Show classes and
        // properties only draws the page again, as does Review changes once
        // the namespace URI is no longer a vocabulary's the admin may update.
        $button = null;
        if ($request->getPost('update')) {
            $button = 'review';
        } elseif ($request->getPost('preview')) {
            $button = 'show';
        } elseif ($request->isPost()) {
            $button = 'import';
        }
        // A failed import says why itself, so the list doesn't say it again.
        $refused = false;
        $saidOmekaHasIt = false;
        if ('review' === $button && $updatable) {
            // Review changes: hand the file to Update's review. Only the CSRF
            // field is checked, as the review's own start checks the rest.
            $form->setValidationGroup(['csrf']);
            if (!$form->isValid()) {
                $this->messenger()->addFormErrors($form);
            } else {
                try {
                    // With this page's choices, a default chosen among them;
                    // one not made is read as the vocabulary was. A namespace
                    // URI in another form changes to the file's with the rest.
                    $reviewUrl = $this->vocabularyReview->start($updatable->id(), $import['file'], ['format' => $format] + $chosen, ['new_namespace_uri' => $namespaceUri]);
                    // The review keeps its own copy, so the import is over.
                    $this->vocabularyImport->end($token);
                    return $this->redirect()->toUrl($reviewUrl);
                } catch (\Exception $e) {
                    $this->messenger()->addErrors($this->getErrors($e));
                }
            }
        } elseif ('import' === $button) {
            if (!$form->isValid()) {
                $this->messenger()->addFormErrors($form);
            } elseif ($other) {
                // Importing it would duplicate its terms.
                $this->messenger()->addError($this->getAlreadyHasMessage($other));
                $saidOmekaHasIt = true;
            } else {
                try {
                    $response = $this->rdfImporter->import('file', $form->getData()['vocabulary-info'], $options);
                    $this->vocabularyImport->end($token);
                    $this->messenger()->addSuccess($this->getImportedMessage());
                    return $this->redirect()->toUrl($response->getContent()->url());
                } catch (\Exception $e) {
                    $refused = true;
                    $this->messenger()->addErrors($this->getErrors($e));
                }
            }
        }
        if ($other && !$saidOmekaHasIt) {
            // Said before an import is refused for it.
            $this->messenger()->addWarning($this->getAlreadyHasMessage($other));
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('details', $details);
        // The vocabulary Review changes updates from the file.
        $view->setVariable('updatable', $updatable);
        // None under a namespace URI Omeka has, since importing under it is
        // refused.
        $view->setVariable('members', $other ? null : $this->getImportPreview($namespaceUri, $options, !$refused));
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
     * Update a vocabulary from a newer file, by way of a review.
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
     * Show the changes a review's file would make, with the page's choices.
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
     * Accept a review's changes.
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
     * Get what an import's file says of its vocabulary.
     *
     * @see RdfImporter::getVocabularyDetails()
     * @param string $file
     * @param string $format
     * @return array
     */
    protected function getImportDetails($file, $format)
    {
        return $this->rdfImporter->getVocabularyDetails('file', [
            'file' => $file,
            'format' => $format,
            // As "en_US", which the importer matches as en-us.
            'lang' => $this->settings()->get('locale'),
        ]);
    }

    /**
     * Get the import details form, filled in with what an import's file says
     * of its vocabulary, or what a module handed the import in its place.
     *
     * @param array $details As getImportDetails() gives them
     * @param array $import As VocabularyImport::get() gives it
     * @return VocabularyForm
     */
    protected function getImportDetailsForm(array $details, array $import)
    {
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'import_details', 'languages' => $details['languages']]);
        $form->setData([
            'vocabulary-info' => array_replace([
                'o:label' => $details['label'],
                'o:comment' => $details['comment'],
                'o:namespace_uri' => $details['namespace_uri'],
                'o:prefix' => $details['prefix'],
            ], $import['vocabulary']),
            'vocabulary-advanced' => [
                // Nearest the installation's, else "", First available.
                'lang' => $details['lang'] ?? '',
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
     * Get the import options to import and to list the classes and properties
     * with: the file, its format, and each choice, null where none was made.
     *
     * @param array $import As VocabularyImport::get() gives it
     * @param string $format
     * @param array $chosen As VocabularyForm::getChosenImportOptions() gives them
     * @return array
     */
    protected function getImportOptions(array $import, $format, array $chosen)
    {
        $options = ['file' => $import['file'], 'format' => $format];
        foreach (VocabularyImport::CHOICES as $choice) {
            $options[$choice] = $chosen[$choice] ?? null;
        }
        return $options;
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
    protected function getImportPreview($namespaceUri, array $options, $sayWhy)
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
