<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\NamespaceUri;
use Omeka\Stdlib\VocabularyFileStore;
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
     * @var VocabularyFileStore
     */
    protected $fileStore;

    /**
     * @param RdfImporter $rdfImporter
     * @param VocabularyReview $vocabularyReview
     * @param VocabularyFileStore $fileStore
     */
    public function __construct(RdfImporter $rdfImporter, VocabularyReview $vocabularyReview,
        VocabularyFileStore $fileStore
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->vocabularyReview = $vocabularyReview;
        $this->fileStore = $fileStore;
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
     * Show a vocabulary: its own fields, its classes and properties, and the
     * pages that change it, where the user may change it. Each of those
     * pages comes back here, whether it's done or cancelled.
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
        $view->setVariable('partialPath', 'omeka/admin/vocabulary/delete-confirm-details');
        return $view;
    }

    /**
     * Import a vocabulary, first step: take its file, read what it says of
     * itself, and go on to the details, with the file kept.
     */
    public function importAction()
    {
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'import_file']);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData()['vocabulary-file'];
                $source = $this->getFileSource($data);
                if (!$source) {
                    $this->messenger()->addError('Choose a file or enter a URL.'); // @translate
                } else {
                    $token = null;
                    try {
                        $token = isset($source['file'])
                            ? $this->fileStore->keep($source['file'])
                            : $this->fileStore->keepFromUrl($source['url']);
                        $details = $this->getImportDetails($token, $data['format']);
                        // A vocabulary Omeka has, in any form of the
                        // namespace, is updated from its page: imported, its
                        // terms would be twice.
                        $other = null === $details['namespace_uri'] ? null : $this->getVocabularyWithNamespace($details['namespace_uri']);
                        if ($other) {
                            $this->fileStore->remove($token);
                            $this->messenger()->addError($this->getAlreadyHasMessage($other));
                        } else {
                            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import-details'], [
                                'query' => ['file' => $token, 'format' => $data['format']],
                            ]);
                        }
                    } catch (\Exception $e) {
                        if (null !== $token) {
                            $this->fileStore->remove($token);
                        }
                        $this->messenger()->addErrors($this->getErrors($e));
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Import a vocabulary, second step: its own fields and how its file is
     * read, starting as the file says, then import it from the kept file.
     * The query gives the file's token and format.
     */
    public function importDetailsAction()
    {
        $token = (string) $this->params()->fromQuery('file');
        $format = (string) $this->params()->fromQuery('format');
        if (!in_array($format, ['guess', 'jsonld', 'ntriples', 'rdfxml', 'turtle'], true)) {
            $format = 'guess';
        }
        $path = $this->fileStore->getPath($token);
        if (null === $path) {
            $this->messenger()->addError('This import has ended. Choose the file again.'); // @translate
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        try {
            $details = $this->getImportDetails($token, $format);
        } catch (\Exception $e) {
            $this->fileStore->remove($token);
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'import_details', 'languages' => $details['languages']]);
        $form->setData([
            'vocabulary-info' => [
                'o:label' => $details['label'],
                'o:comment' => $details['comment'],
                'o:namespace_uri' => $details['namespace_uri'],
                'o:prefix' => $details['prefix'],
            ],
            'vocabulary-advanced' => [
                'lang' => $this->getDefaultLanguage($details['languages']),
                'comments' => 'comment',
            ],
        ]);
        $request = $this->getRequest();
        if ($request->isPost()) {
            // The form posts to the page's own URL, query and all.
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $data = $form->getData();
                $other = $this->getVocabularyWithNamespace($data['vocabulary-info']['o:namespace_uri']);
                if ($other) {
                    $this->messenger()->addError($this->getAlreadyHasMessage($other));
                } else {
                    $advanced = $data['vocabulary-advanced'];
                    $options = [
                        'file' => $path,
                        'format' => $format,
                        'lang' => '' === (string) $advanced['lang'] ? null : $advanced['lang'],
                        'label_property' => $advanced['label_property'],
                        // A comment property entered is used in place of the
                        // Comments choice.
                        'comment_property' => $advanced['comment_property']
                            ?? RdfImporter::COMMENT_READINGS[$advanced['comments'] ?? 'comment']
                            ?? null,
                    ];
                    try {
                        $response = $this->rdfImporter->import('file', $data['vocabulary-info'], $options);
                        $this->fileStore->remove($token);
                        $message = new Message(
                            'Vocabulary successfully imported. %s', // @translate
                            sprintf(
                                '<a href="%s">%s</a>',
                                htmlspecialchars($this->url()->fromRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import'])),
                                $this->translate('Import another vocabulary?')
                            )
                        );
                        $message->setEscapeHtml(false);
                        $this->messenger()->addSuccess($message);
                        // To the new vocabulary's page, which shows what came in.
                        return $this->redirect()->toUrl($response->getContent()->url());
                    } catch (\Exception $e) {
                        // The file is kept, to try again.
                        $this->messenger()->addErrors($this->getErrors($e));
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('details', $details);
        return $view;
    }

    /**
     * Edit a vocabulary's label and comment. Its namespace URI is changed on
     * a page of its own, and its classes and properties are updated from a
     * file on another.
     */
    public function editAction()
    {
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary]);
        if ($vocabulary->isPermanent()) {
            throw new Exception\PermissionDeniedException('Cannot edit a permanent vocabulary');
        }
        if (!$vocabulary->userIsAllowed('update')) {
            throw new Exception\PermissionDeniedException('User does not have permission to edit this vocabulary');
        }
        $form->setData([
            'vocabulary-info' => [
                'o:label' => $vocabulary->label(),
                'o:comment' => $vocabulary->comment(),
            ],
        ]);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                // Where the API refuses the vocabulary's own fields, it puts
                // its reasons on the form, which is shown again.
                $response = $this->api($form)->update('vocabularies', $this->params('id'), $form->getData()['vocabulary-info'], [], ['isPartial' => true]);
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
     * Change a vocabulary's namespace URI, which changes the URI of every one
     * of its classes and properties. The field starts with the vocabulary's
     * own, to edit.
     */
    public function changeNamespaceAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary, 'mode' => 'change_namespace']);
        $form->setData([
            'vocabulary-info' => [
                'o:namespace_uri' => $vocabulary->namespaceUri(),
            ],
        ]);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $namespaceUri = $form->getData()['vocabulary-info']['o:namespace_uri'];
                $other = $this->getVocabularyInAnotherForm($namespaceUri, $vocabulary);
                if ($namespaceUri === $vocabulary->namespaceUri()) {
                    $this->messenger()->addError('The vocabulary already has this namespace URI.'); // @translate
                } elseif ($other) {
                    // As an import is refused: their terms would be twice.
                    $this->messenger()->addError(new Message(
                        'Another vocabulary, "%1$s", has this namespace URI in another form: "%2$s".', // @translate
                        $other->label(),
                        $other->namespaceUri()
                    ));
                } else {
                    // Where the API refuses it, as one another vocabulary
                    // has, it puts its reasons on the form, which is shown
                    // again.
                    $response = $this->api($form)->update('vocabularies', $vocabulary->id(), ['o:namespace_uri' => $namespaceUri], [], ['isPartial' => true]);
                    if ($response) {
                        $this->messenger()->addSuccess('Namespace successfully changed'); // @translate
                        return $this->redirect()->toUrl($vocabulary->url());
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Update a vocabulary from a newer version of its file: take the file,
     * and review the changes it makes, which nothing saves until accepted.
     */
    public function updateAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary, 'mode' => 'update_from_file']);
        // In the format the vocabulary's file was read in, unless changed.
        $format = $vocabulary->importOptions()['format'] ?? null;
        if (null !== $format) {
            $form->setData(['vocabulary-file' => ['format' => $format]]);
        }
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData()['vocabulary-file'];
                $source = $this->getFileSource($data);
                if (!$source) {
                    $this->messenger()->addError('Choose a file or enter a URL.'); // @translate
                } else {
                    // How the file is read starts as the vocabulary's was, and
                    // is chosen on the review.
                    $reading = ['format' => $data['format']];
                    try {
                        $reviewUrl = isset($source['file'])
                            ? $this->vocabularyReview->start($vocabulary->id(), $source['file'], $reading)
                            : $this->vocabularyReview->startFromUrl($vocabulary->id(), $source['url'], $reading);
                        return $this->redirect()->toUrl($reviewUrl);
                    } catch (\Exception $e) {
                        $namespaceUri = $this->getFileNamespaceUri($e);
                        if (null === $namespaceUri) {
                            $this->messenger()->addErrors($this->getErrors($e));
                        } else {
                            // The file's terms are in another namespace: say
                            // which, beside the vocabulary's, as they may
                            // differ by a character, and how to move it.
                            $message = new Message(
                                'None of this file\'s classes or properties are in this vocabulary\'s namespace, "%1$s". They use "%2$s". If the vocabulary has moved, change its namespace, then update it again.', // @translate
                                $vocabulary->namespaceUri(),
                                $namespaceUri
                            );
                            $this->messenger()->addError($message);
                        }
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Show the changes a review's file would make, read as the admin
     * chooses, for them to accept or cancel.
     */
    public function reviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $reading = $this->vocabularyReview->getReading($review, $this->getChoices($this->params()->fromQuery()));

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('reading', $reading);
        $view->setVariable('form', $this->getForm(ConfirmForm::class));
        $view->setVariable('returnUrl', $this->vocabularyReview->getReturnUrl($review));
        try {
            $view->setVariable('diff', $this->vocabularyReview->getDiff($review, $reading));
            $view->setVariable('missing', $this->vocabularyReview->getMissingTerms($review, $reading));
            $view->setVariable('languages', $this->vocabularyReview->getLanguages($review, $reading));
        } catch (\Exception $e) {
            // Read another way, the file may not do; say why, and leave the
            // choices to change.
            $this->messenger()->addErrors($this->getErrors($e));
            $view->setVariable('diff', null);
            $view->setVariable('missing', null);
            $view->setVariable('languages', []);
        }
        return $view;
    }

    /**
     * Accept a review's changes, read as chosen on the review page.
     */
    public function acceptAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $post = $this->params()->fromPost();
        $token = (string) ($post['review'] ?? '');
        $review = $this->getRequest()->isPost() ? $this->getReview($token, $vocabulary) : null;
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $choices = $this->getChoices($post);
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        try {
            $this->vocabularyReview->accept($token, $this->vocabularyReview->getReading($review, $choices));
        } catch (ValidationException $e) {
            // Nothing was changed, and the review goes on.
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        $this->messenger()->addSuccess('Changes to the vocabulary successfully made'); // @translate
        return $this->redirect()->toUrl($this->vocabularyReview->getReturnUrl($review));
    }

    /**
     * Cancel a review, changing nothing.
     */
    public function cancelAction()
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
        return $this->redirect()->toUrl($this->vocabularyReview->getReturnUrl($review));
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
     * Get the vocabulary of the request, refusing one the user may not
     * change: a permanent one, or another's.
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
     * Get a review of the vocabulary, saying why where there's none.
     *
     * @param string $token
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return array|null
     */
    protected function getReview($token, $vocabulary)
    {
        $review = $this->vocabularyReview->get($token);
        if (!$review || $review['vocabulary_id'] !== $vocabulary->id()) {
            $this->messenger()->addError('This review has ended. Update the vocabulary\'s classes and properties to review its changes again.'); // @translate
            return null;
        }
        return $review;
    }

    /**
     * Get the reading options the admin chose on the review page: its
     * preferred language ("" for the first available), where comments are
     * read from, and any label or comment property entered, which takes the
     * place of the importer's default ("" for the default).
     *
     * @param array $params The page's query, or the post of its form
     * @return array
     */
    protected function getChoices(array $params)
    {
        $choices = [];
        if (isset($params['lang']) && is_string($params['lang'])) {
            $choices['lang'] = '' === $params['lang'] ? null : $params['lang'];
        }
        if (isset($params['label_property']) && is_string($params['label_property'])) {
            $choices['label_property'] = '' === trim($params['label_property']) ? null : trim($params['label_property']);
        }
        if (isset($params['comments']) && is_string($params['comments']) && isset(RdfImporter::COMMENT_READINGS[$params['comments']])) {
            $choices['comment_property'] = RdfImporter::COMMENT_READINGS[$params['comments']];
        }
        // A comment property entered is used in place of the Comments
        // choice, as on the import form.
        if (isset($params['comment_property']) && is_string($params['comment_property']) && '' !== trim($params['comment_property'])) {
            $choices['comment_property'] = trim($params['comment_property']);
        }
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
        foreach (['lang', 'comments', 'label_property', 'comment_property'] as $key) {
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
     * Get the file a form's file fields choose: an upload, or a URL.
     *
     * @param array $data The form's file fieldset, as validated
     * @return array|null ['file' => the upload's path] or ['url' => URL], or
     *   null where none was chosen
     */
    protected function getFileSource(array $data)
    {
        // The form has already refused a file that failed to upload, so an
        // empty temp file name means none was chosen.
        $file = $data['file'] ?? [];
        $url = trim((string) ($data['url'] ?? ''));
        if ('upload' === $data['import_type'] && !empty($file['tmp_name'])) {
            return ['file' => $file['tmp_name']];
        }
        if ('url' === $data['import_type'] && '' !== $url) {
            return ['url' => $url];
        }
        return null;
    }

    /**
     * Get what a kept file says of its vocabulary, its title and description
     * read in the installation's language where it has them.
     *
     * @see RdfImporter::getVocabularyDetails()
     * @param string $token The file's token in the file store
     * @param string $format
     * @return array
     */
    protected function getImportDetails($token, $format)
    {
        return $this->rdfImporter->getVocabularyDetails('file', [
            'file' => $this->fileStore->getPath($token),
            'format' => $format,
            'lang' => strtok($this->getLocaleLanguage(), '-') ?: null,
        ]);
    }

    /**
     * Get the language a file's terms are read in by default: the
     * installation's language if the file has it, else English, else the
     * first available ("").
     *
     * @param string[] $languages The file's languages
     * @return string
     */
    protected function getDefaultLanguage(array $languages)
    {
        $locale = $this->getLocaleLanguage();
        $primary = strtok($locale, '-');
        // The tag itself, then its language in any region, as fr-CA for fr.
        foreach (array_unique(array_filter([$locale, $primary, 'en'])) as $wanted) {
            foreach ($languages as $language) {
                if (strtolower($language) === $wanted) {
                    return $language;
                }
            }
            foreach ($languages as $language) {
                if (0 === strpos(strtolower($language), strtok($wanted, '-') . '-')) {
                    return $language;
                }
            }
        }
        return '';
    }

    /**
     * Get the installation's language as a language tag, such as "fr" or
     * "en-us".
     *
     * @return string
     */
    protected function getLocaleLanguage()
    {
        return strtolower(str_replace('_', '-', (string) ($this->settings()->get('locale') ?: 'en')));
    }

    /**
     * Get a vocabulary whose namespace URI is a namespace URI, or another
     * form of it.
     *
     * @param string $namespaceUri
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyWithNamespace($namespaceUri)
    {
        $vocabularies = $this->api()->search('vocabularies', ['namespace_uri' => NamespaceUri::getForms($namespaceUri)])->getContent();
        return $vocabularies[0] ?? null;
    }

    /**
     * Get the message that Omeka already has a vocabulary being imported,
     * saying how to update it only to someone who may.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return Message
     */
    protected function getAlreadyHasMessage($vocabulary)
    {
        $message = new Message(
            !$vocabulary->isPermanent() && $vocabulary->userIsAllowed('update')
                ? 'Omeka already has this vocabulary, as "%1$s" with the namespace URI "%2$s". To update it, use Update classes and properties on its page.' // @translate
                : 'Omeka already has this vocabulary, as "%1$s" with the namespace URI "%2$s".', // @translate
            sprintf('<a href="%s">%s</a>', htmlspecialchars($vocabulary->url()), htmlspecialchars($vocabulary->label())),
            htmlspecialchars($vocabulary->namespaceUri())
        );
        $message->setEscapeHtml(false);
        return $message;
    }

    /**
     * Get a vocabulary whose namespace URI is another form of one, ending in
     * "#", "/", or neither, with http or https.
     *
     * @param string $namespaceUri
     * @param \Omeka\Api\Representation\VocabularyRepresentation|null $except
     *   A vocabulary not to count, such as one whose namespace URI changes
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyInAnotherForm($namespaceUri, $except = null)
    {
        $vocabularies = $this->api()->search('vocabularies', ['namespace_uri' => NamespaceUri::getForms($namespaceUri)])->getContent();
        foreach ($vocabularies as $vocabulary) {
            if ($vocabulary->namespaceUri() !== $namespaceUri
                && (!$except || $vocabulary->id() !== $except->id())
            ) {
                return $vocabulary;
            }
        }
        return null;
    }

    /**
     * Get the namespace URI a file's classes and properties use, where the
     * importer refused the file for having none in the vocabulary's. It's
     * the one argument of the importer's message under o:namespace_uri.
     *
     * @see RdfImporter::checkNamespaceUri()
     * @param \Exception $e
     * @return string|null
     */
    protected function getFileNamespaceUri(\Exception $e)
    {
        if (!$e instanceof ValidationException) {
            return null;
        }
        foreach ($e->getErrorStore()->getErrors()['o:namespace_uri'] ?? [] as $message) {
            if ($message instanceof Message && $message->hasArgs()) {
                return (string) $message->getArgs()[0];
            }
        }
        return null;
    }

    /**
     * Get the messages of an exception, with those in its error store, where
     * the importer names the terms at fault.
     *
     * @param \Exception $e
     * @return array
     */
    protected function getErrors(\Exception $e)
    {
        $errors = [$e->getMessage()];
        if (method_exists($e, 'getErrorStore')) {
            $errors[] = $e->getErrorStore()->getErrors();
        }
        return $errors;
    }
}
