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

    foreach ($members['o:class'] as $class) {
        $msgids[$class['o:label']][] = sprintf(
            '#. Class label for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $class['o:local_name']
        );
        $msgids[$class['o:comment']][] = sprintf(
            '#. Class comment for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $class['o:local_name']
        );
    }

    foreach ($members['o:property'] as $property) {
        $msgids[$property['o:label']][] = sprintf(
            '#. Property label for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $property['o:local_name']
        );
        $msgids[$property['o:comment']][] = sprintf(
            '#. Property comment for %s:%s',
            $defaultVocab['vocabulary']['o:label'],
            $property['o:local_name']
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
