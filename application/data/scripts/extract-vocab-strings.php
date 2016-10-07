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
        '#: %s vocabulary label',
        $defaultVocab['vocabulary']['o:label']
    );
    $msgids[$defaultVocab['vocabulary']['o:comment']][] = sprintf(
        '#: %s vocabulary comment',
        $defaultVocab['vocabulary']['o:label']
    );

    foreach ($members['o:class'] as $class) {
        $msgids[$class['o:label']][] = sprintf(
            '#: %s : %s class label',
            $defaultVocab['vocabulary']['o:label'],
            $class['o:local_name']
        );
        $msgids[$class['o:comment']][] = sprintf(
            '#: %s : %s class comment',
            $defaultVocab['vocabulary']['o:label'],
            $class['o:local_name']
        );
    }

    foreach ($members['o:property'] as $property) {
        $msgids[$property['o:label']][] = sprintf(
            '#: %s : %s property label',
            $defaultVocab['vocabulary']['o:label'],
            $property['o:local_name']
        );
        $msgids[$property['o:comment']][] = sprintf(
            '#: %s : %s property comment',
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
