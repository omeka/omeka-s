<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Entity\Vocabulary;
use Omeka\Form\VocabularyForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
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
     * Show a vocabulary. The pages that change it come back here.
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
     * Import a vocabulary, first step: store the chosen file in the file
     * store, and go on to the details step.
     */
    public function importAction()
    {
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'file']);
        $source = $this->getRequest()->isPost() ? $this->getPostedFileSource($form) : null;
        if ($source) {
            $token = null;
            try {
                $token = isset($source['file'])
                    ? $this->fileStore->keep($source['file'])
                    : $this->fileStore->keepFromUrl($source['url']);
                // Refuse a file that can't be read, or has no terms, now.
                $this->getImportDetails($token, $source['format']);
                return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import-details'], [
                    'query' => ['file_token' => $token, 'format' => $source['format']],
                ]);
            } catch (\Exception $e) {
                if (null !== $token) {
                    $this->fileStore->remove($token);
                }
                $this->messenger()->addErrors($this->getErrors($e));
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Import a vocabulary, second step: its fields and import options,
     * filled in from the file. The query gives the file's token in the file
     * store, and its format.
     */
    public function importDetailsAction()
    {
        $token = (string) $this->params()->fromQuery('file_token');
        $format = (string) $this->params()->fromQuery('format');
        if (!isset(VocabularyForm::FORMATS[$format])) {
            $format = 'guess';
        }
        $path = $this->fileStore->getPath($token);
        if (null === $path) {
            $this->messenger()->addError('This import has ended. Choose the file or enter its URL again.'); // @translate
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
        // The namespace URI found may be another vocabulary's, as when a
        // file redeclares terms it reuses.
        if (!$request->isPost() && null !== $details['namespace_uri']) {
            $other = $this->getVocabularyWithNamespace($details['namespace_uri']);
            if ($other) {
                $this->messenger()->addWarning($this->getAlreadyHasMessage($other, true));
            }
        }
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $data = $form->getData();
                // Refuse a vocabulary Omeka already has under any form of
                // this namespace URI: importing it would duplicate its terms.
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
                        'comment_property' => $this->getChosenCommentProperty($advanced),
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
                        return $this->redirect()->toUrl($response->getContent()->url());
                    } catch (\Exception $e) {
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
                // Compared before the update, which changes what
                // $vocabulary reads.
                $moved = $info['o:namespace_uri'] !== $vocabulary->namespaceUri();
                // Refuse another vocabulary's namespace URI in a different
                // form, such as https://schema.org/ where it has
                // http://schema.org/. The API refuses an exact match itself.
                $other = $moved ? $this->getVocabularyInAnotherForm($info['o:namespace_uri'], $vocabulary) : null;
                if ($other) {
                    $this->messenger()->addError(new Message(
                        'Another vocabulary, "%1$s", has this namespace URI in another form: "%2$s".', // @translate
                        $other->label(),
                        $other->namespaceUri()
                    ));
                } else {
                    // The API puts its refusals on the form.
                    $response = $this->api($form)->update('vocabularies', $this->params('id'), $info, [], ['isPartial' => true]);
                    if ($response) {
                        if (!$moved) {
                            $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                        } else {
                            // A vocabulary that has moved is usually updated
                            // from its file at the new namespace next.
                            $message = new Message(
                                'Vocabulary successfully updated. %s', // @translate
                                sprintf(
                                    '<a href="%s">%s</a>',
                                    htmlspecialchars($vocabulary->url('update')),
                                    $this->translate('Update its classes and properties?')
                                )
                            );
                            $message->setEscapeHtml(false);
                            $this->messenger()->addSuccess($message);
                        }
                        return $this->redirect()->toUrl($response->getContent()->url());
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
     * Update a vocabulary from a newer version of its file, by way of a
     * review.
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
                $namespaceUri = $this->getFileNamespaceUri($e);
                if (null === $namespaceUri) {
                    $this->messenger()->addErrors($this->getErrors($e));
                } else {
                    // Name both namespace URIs: they may differ only by a #
                    // or / that's easy to miss. "change its namespace" links
                    // to Edit, where the namespace is changed.
                    $message = new Message(
                        'None of this file\'s classes or properties are in this vocabulary\'s namespace, "%1$s". They use "%2$s". If the vocabulary has moved, %3$schange its namespace%4$s, then update it again.', // @translate
                        htmlspecialchars($vocabulary->namespaceUri()),
                        htmlspecialchars($namespaceUri),
                        sprintf('<a href="%s">', htmlspecialchars($vocabulary->url('edit'))),
                        '</a>'
                    );
                    $message->setEscapeHtml(false);
                    $this->messenger()->addError($message);
                }
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Show the changes a review's file would make, read with the import
     * options chosen on the page.
     */
    public function reviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $importOptions = $this->vocabularyReview->getImportOptions($review, $this->getChoices($this->params()->fromQuery()));

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('importOptions', $importOptions);
        $view->setVariable('form', $this->getForm(ConfirmForm::class));
        try {
            $view->setVariable('diff', $this->vocabularyReview->getDiff($review, $importOptions));
            $view->setVariable('notInFile', $this->vocabularyReview->getMembersNotInFile($review, $importOptions));
            $view->setVariable('languages', $this->vocabularyReview->getLanguages($review, $importOptions));
        } catch (\Exception $e) {
            // The chosen import options may make the file unreadable: say
            // why, and keep the choices to change.
            $this->messenger()->addErrors($this->getErrors($e));
            $view->setVariable('diff', null);
            $view->setVariable('notInFile', null);
            $view->setVariable('languages', []);
        }
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
        $choices = $this->getChoices($post);
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        try {
            $this->vocabularyReview->accept($token, $this->vocabularyReview->getImportOptions($review, $choices));
        } catch (ValidationException $e) {
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        $this->messenger()->addSuccess('Changes to the vocabulary successfully made'); // @translate
        return $this->redirect()->toUrl($this->vocabularyReview->getReturnUrl($review));
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
     * change.
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
            $this->messenger()->addError('This review has ended. To review the changes again, use Update classes and properties.'); // @translate
            return null;
        }
        return $review;
    }

    /**
     * Get the import options chosen on the review page.
     *
     * @param array $params The page's query, or its form's post
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
        $commentProperty = $this->getChosenCommentProperty($params);
        if (null !== $commentProperty) {
            $choices['comment_property'] = $commentProperty;
        }
        return $choices;
    }

    /**
     * Get the comment property chosen: one the Comments field offers, or the
     * one entered under "Another property…". Another with none entered is no
     * choice.
     *
     * @param array $params With "comments" and "comment_property"
     * @return string|null
     */
    protected function getChosenCommentProperty(array $params)
    {
        $comments = $params['comments'] ?? null;
        if (!is_string($comments)) {
            return null;
        }
        if ('other' === $comments) {
            $property = is_string($params['comment_property'] ?? null) ? trim($params['comment_property']) : '';
            return '' === $property ? null : $property;
        }
        return RdfImporter::COMMENT_PROPERTY_CHOICES[$comments] ?? null;
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
     * Get what a file in the file store says of its vocabulary.
     *
     * @see RdfImporter::getVocabularyDetails()
     * @param string $token
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
     * Get the default language to read a file's terms in: the installation's,
     * else English, else "", the "First available" option.
     *
     * @param string[] $languages The file's languages
     * @return string
     */
    protected function getDefaultLanguage(array $languages)
    {
        $locale = $this->getLocaleLanguage();
        $primary = strtok($locale, '-');
        // In order: the installation's exact tag (en-us), its language alone
        // (en), its language in any region (en-GB), then English the same
        // way. A wanted tag ending in "-" matches any region.
        foreach (array_unique([$locale, $primary, $primary . '-', 'en', 'en-']) as $wanted) {
            foreach ($languages as $language) {
                $tag = strtolower($language);
                if ($tag === $wanted || ('-' === substr($wanted, -1) && 0 === strpos($tag, $wanted))) {
                    return $language;
                }
            }
        }
        return '';
    }

    /**
     * Get the installation's language as a tag, such as "en-us".
     *
     * @return string
     */
    protected function getLocaleLanguage()
    {
        return strtolower(str_replace('_', '-', (string) ($this->settings()->get('locale') ?: 'en')));
    }

    /**
     * Get a vocabulary with a namespace URI, in any form.
     *
     * @param string $namespaceUri
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyWithNamespace($namespaceUri)
    {
        return $this->api()->searchOne('vocabularies', ['namespace_uri' => Vocabulary::getNamespaceUriForms($namespaceUri)])->getContent();
    }

    /**
     * Get the message that Omeka already has a vocabulary being imported.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param bool $found Whether its namespace URI was read from the file,
     *   rather than entered by the admin
     * @return Message
     */
    protected function getAlreadyHasMessage($vocabulary, $found = false)
    {
        $mayUpdate = !$vocabulary->isPermanent() && $vocabulary->userIsAllowed('update');
        $link = sprintf('<a href="%s">%s</a>', htmlspecialchars($vocabulary->url()), htmlspecialchars($vocabulary->label()));
        $namespaceUri = htmlspecialchars($vocabulary->namespaceUri());
        if ($found) {
            $message = new Message(
                $mayUpdate
                    ? 'Omeka already has a vocabulary with the namespace URI this file appears to use, "%1$s": %2$s. If the file is that vocabulary, use Update classes and properties on its page instead. If not, enter the file\'s own namespace URI below.' // @translate
                    : 'Omeka already has a vocabulary with the namespace URI this file appears to use, "%1$s": %2$s. If the file is a different vocabulary, enter its own namespace URI below.', // @translate
                $namespaceUri,
                $link
            );
        } else {
            $message = new Message(
                $mayUpdate
                    ? 'Omeka already has this vocabulary, as "%1$s" with the namespace URI "%2$s". To update it, use Update classes and properties on its page.' // @translate
                    : 'Omeka already has this vocabulary, as "%1$s" with the namespace URI "%2$s".', // @translate
                $link,
                $namespaceUri
            );
        }
        $message->setEscapeHtml(false);
        return $message;
    }

    /**
     * Get a vocabulary, other than $except, whose namespace URI is a
     * different form of this one. An exact match isn't returned.
     *
     * @param string $namespaceUri
     * @param \Omeka\Api\Representation\VocabularyRepresentation $except
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyInAnotherForm($namespaceUri, $except)
    {
        $vocabularies = $this->api()->search('vocabularies', ['namespace_uri' => Vocabulary::getNamespaceUriForms($namespaceUri)])->getContent();
        foreach ($vocabularies as $vocabulary) {
            if ($vocabulary->namespaceUri() !== $namespaceUri && $vocabulary->id() !== $except->id()) {
                return $vocabulary;
            }
        }
        return null;
    }

    /**
     * Where the importer refused a file for having no terms in the
     * namespace, get the namespace URI its terms do use: the one argument of
     * its o:namespace_uri message.
     *
     * @see RdfImporter::checkHasMembers()
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
     * Get the messages of an exception, with those in its error store, or
     * else that something went wrong.
     *
     * @param \Exception $e
     * @return array
     */
    protected function getErrors(\Exception $e)
    {
        // An API refusal has no message of its own, only its reasons.
        $errors = '' === (string) $e->getMessage() ? [] : [$e->getMessage()];
        if (method_exists($e, 'getErrorStore') && $e->getErrorStore()->getErrors()) {
            $errors[] = $e->getErrorStore()->getErrors();
        }
        return $errors ?: ['Something went wrong']; // @translate
    }
}
