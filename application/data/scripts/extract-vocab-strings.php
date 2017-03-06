<?php
/**
 * Extract labels and comments from bundled vocabularies and output POT message IDs.
 */
use Omeka\Installation\Task\InstallDefaultVocabulariesTask;

require dirname(dirname(dirname(__DIR__))) . '/bootstrap.php';
$config = require OMEKA_PATH . '/application/config/application.config.php';
$application = Zend\Mvc\Application::init($config);
$services = $application->getServiceManager();

$rdfImporter = $services->get('Omeka\RdfImporter');
$defaultVocabs = (new InstallDefaultVocabulariesTask)->getVocabularies();

// Build the msgids and their comments.
$msgids = [];
foreach ($defaultVocabs as $defaultVocab) {
    $members = $rdfImporter->getMembers(
        $defaultVocab['strategy'],
        $defaultVocab['vocabulary']['o:namespace_uri'],
        [
            'file' => sprintf(
                '%s/application/data/vocabularies/%s',
                OMEKA_PATH,
                $defaultVocab['file']
            ),
            'format' => $defaultVocab['format'],
        ]
    );

    $msgids[$defaultVocab['vocabulary']['o:label']][] = sprintf(
        '#. Vocabulary label for %s',
        $defaultVocab['vocabulary']['o:label']
    );
    $msgids[$defaultVocab['vocabulary']['o:comment']][] = sprintf(
        '#. Vocabulary comment for %s',
        $defaultVocab['vocabulary']['o:label']
    );

    foreach ($members['classes'] as $localName => $info) {
        $msgids[$info['label']][] = sprintf(
            '#. Class label for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $localName
        );
        $msgids[$info['comment']][] = sprintf(
            '#. Class comment for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $localName
        );
    }

    foreach ($members['properties'] as $localName => $info) {
        $msgids[$info['label']][] = sprintf(
            '#. Property label for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $localName
        );
        $msgids[$info['comment']][] = sprintf(
            '#. Property comment for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $localName
        );
    }
}

// Output the POT file.
$template = <<<POT
msgid "%s"
msgstr ""


POT;

$output = '';
foreach ($msgids as $msgid => $comments) {
    foreach ($comments as $comment) {
        $output .= $comment . PHP_EOL;
    }
    $output .= sprintf($template, addcslashes($msgid, "\n\"\\"));
}

echo $output;
