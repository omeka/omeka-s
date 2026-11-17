<?php
namespace Omeka\Stdlib;

use EasyRdf\Graph as RdfGraph;
use EasyRdf\Resource as RdfResource;
use Omeka\Entity\Vocabulary;

/**
 * What a vocabulary file says of its own vocabulary, its prefix, title, and
 * description, read from its "records": the resources that describe it, such
 * as an owl:Ontology, the namespace URI itself, or the resource its terms name
 * with rdfs:isDefinedBy.
 */
class VocabularyRecords
{
    /**
     * Get the prefix a file declares for its vocabulary
     * (vann:preferredNamespacePrefix), if it's a valid Omeka prefix. Only this
     * namespace's records are read, as a file may declare prefixes for
     * vocabularies it reuses.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return string|null
     */
    public static function getPrefix(RdfGraph $graph, $namespaceUri)
    {
        $subjects = self::getOwnOntologies($graph, $namespaceUri);
        $subjects[] = $graph->resource($namespaceUri);
        $subjects[] = $graph->resource(rtrim($namespaceUri, '#/'));
        foreach ($subjects as $subject) {
            try {
                $prefix = trim((string) $subject->get('<http://purl.org/vocab/vann/preferredNamespacePrefix>'));
            } catch (\Throwable $e) {
                continue;
            }
            if ('' !== $prefix) {
                return preg_match(Vocabulary::PREFIX_REGEX, $prefix) ? $prefix : null;
            }
        }
        return null;
    }

    /**
     * Get a file's text for its vocabulary, such as its title, from the first
     * of its records that has it.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @param string $properties Alternatives, as "a|b"
     * @param string|null $lang
     * @param bool $paragraphs True for a description, keeping its paragraphs
     *   and all its values; false for a title, one value on one line
     * @return string|null
     */
    public static function getText(RdfGraph $graph, $namespaceUri, $properties, $lang, $paragraphs)
    {
        foreach (self::getRecords($graph, $namespaceUri) as $record) {
            try {
                $values = self::getValues($record, $properties, $lang);
            } catch (\Throwable $e) {
                continue;
            }
            $texts = [];
            foreach ($values as $value) {
                $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
                if ('' === $text || !mb_check_encoding($text, 'UTF-8')) {
                    continue;
                }
                if (!$paragraphs) {
                    return preg_replace('/\s+/u', ' ', $text);
                }
                $texts[] = preg_replace("/\n\s*\n\s*/u", "\n\n", $text);
            }
            if ($texts) {
                return implode("\n\n", $texts);
            }
        }
        return null;
    }

    /**
     * Get the records a file may describe its vocabulary in, in the order
     * they're read: its owl:Ontology resources for the namespace, else its
     * only owl:Ontology if it has just one; the namespace URI as a resource,
     * with and without its closing # or /; and the resource most of its
     * terms name with rdfs:isDefinedBy.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return RdfResource[]
     */
    protected static function getRecords(RdfGraph $graph, $namespaceUri)
    {
        $records = self::getOwnOntologies($graph, $namespaceUri);
        if (!$records) {
            $all = $graph->allOfType('owl:Ontology');
            $records = 1 === count($all) ? $all : [];
        }
        $records[] = $graph->resource($namespaceUri);
        $records[] = $graph->resource(rtrim($namespaceUri, '#/'));
        $counts = [];
        foreach ($graph->resources() as $resource) {
            if ($resource->isBNode() || 0 !== strpos($resource->getUri(), $namespaceUri)) {
                continue;
            }
            foreach ($resource->allResources('rdfs:isDefinedBy') as $record) {
                if (!$record->isBNode()) {
                    $counts[$record->getUri()] = ($counts[$record->getUri()] ?? 0) + 1;
                }
            }
        }
        if ($counts) {
            arsort($counts);
            $records[] = $graph->resource(array_key_first($counts));
        }
        return $records;
    }

    /**
     * Get a file's ontology records for a namespace.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return RdfResource[]
     */
    protected static function getOwnOntologies(RdfGraph $graph, $namespaceUri)
    {
        $base = rtrim($namespaceUri, '#/');
        $own = [];
        foreach ($graph->allOfType('owl:Ontology') as $ontology) {
            if (rtrim((string) $ontology->getUri(), '#/') === $base) {
                $own[] = $ontology;
            }
        }
        return $own;
    }

    /**
     * Get a record's values for the first of some properties that has them
     * in a language: the one asked for, then English, then any.
     *
     * @param RdfResource $record
     * @param string $properties Alternatives, as "a|b"
     * @param string|null $lang
     * @return \EasyRdf\Literal[]
     */
    protected static function getValues(RdfResource $record, $properties, $lang)
    {
        foreach (array_unique(array_filter([$lang, 'en'])) as $language) {
            foreach (explode('|', $properties) as $property) {
                $values = array_filter($record->all($property, 'literal'), function ($value) use ($language) {
                    return in_array((string) $value->getLang(), [$language, ''], true);
                });
                if (array_filter($values, function ($value) use ($language) {
                    return $language === $value->getLang();
                })) {
                    return array_values($values);
                }
            }
        }
        foreach (explode('|', $properties) as $property) {
            $values = $record->all($property, 'literal');
            if ($values) {
                $first = (string) $values[0]->getLang();
                return array_values(array_filter($values, function ($value) use ($first) {
                    return in_array((string) $value->getLang(), [$first, ''], true);
                }));
            }
        }
        return [];
    }
}
