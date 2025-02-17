<?php declare(strict_types=1);

/**
 * Map the output from xml checker and standard xml media types.
 *
 * Many Xml media types are not registered, so the unregistered tree (prefix "x")
 * is used, except when the format is public, in which case the vendor tree is
 * used (prefix "vnd").
 *
 * Some formats may need more check in code source.
 *
 * @var array
 *
 * @see \Omeka\File\TempFile
 * @see \BulkImport\Form\Reader\XmlReaderParamsForm
 * @see \Common /data/media-types/media-type-identifiers
 * @see \ExtractText /data/media-types/media-type-identifiers
 * @see \IiifSearch /data/media-types/media-type-identifiers
 * @see \IiifServer\Iiif\TraitIiifType
 */
return [
    'application/xml'                                   => 'application/xml',
    'text/xml'                                          => 'text/xml',

    // Common namespaces (if not managed by fileinfo).

    'http://www.w3.org/2000/svg'                        => 'image/svg+xml',
    'application/vnd.oasis.opendocument.presentation'   => 'application/vnd.oasis.opendocument.presentation-flat-xml',
    'application/vnd.oasis.opendocument.spreadsheet'    => 'application/vnd.oasis.opendocument.spreadsheet-flat-xml',
    'application/vnd.oasis.opendocument.text'           => 'application/vnd.oasis.opendocument.text-flat-xml',
    'http://www.w3.org/1999/xhtml'                      => 'application/xhtml+xml',
    'http://www.w3.org/2005/Atom'                       => 'application/atom+xml',
    'http://purl.org/rss/1.0/'                          => 'application/rss+xml',
    // Common in library and culture world.
    // 'http://bibnum.bnf.fr/ns/alto_prod'              => 'application/vnd.alto+xml', // Deprecated in 2017.
    'http://bibnum.bnf.fr/ns/alto_prod'                 => 'application/alto+xml',
    'http://bibnum.bnf.fr/ns/refNum'                    => 'application/vnd.bnf.refnum+xml',
    'http://www.iccu.sbn.it/metaAG1.pdf'                => 'application/vnd.iccu.mag+xml',
    // 'http://www.loc.gov/MARC21/slim'                 => 'application/vnd.marc21+xml', // Deprecated in 2011.
    'http://www.loc.gov/MARC21/slim'                    => 'application/marcxml+xml',
    // 'http://www.loc.gov/METS/'                       => 'application/vnd.mets+xml', // Deprecated in 2011.
    'http://www.loc.gov/METS/'                          => 'application/mets+xml',
    // 'http://www.loc.gov/mods/'                       => 'application/vnd.mods+xml', // Deprecated in 2011.
    'http://www.loc.gov/mods/'                          => 'application/mods+xml',
    // 'http://www.loc.gov/standards/alto/ns-v3#'       => 'application/vnd.alto+xml', // Deprecated in 2017.
    'http://www.loc.gov/standards/alto/ns-v2#'          => 'application/alto+xml',
    'http://www.loc.gov/standards/alto/ns-v3#'          => 'application/alto+xml',
    'http://www.loc.gov/standards/alto/ns-v4#'          => 'application/alto+xml',
    'http://www.music-encoding.org/ns/mei'              => 'application/vnd.mei+xml',
    'http://www.music-encoding.org/schema/3.0.0/mei-all.rng' => 'application/vnd.mei+xml',
    // See https://github.com/w3c/musicxml/blob/gh-pages/schema/musicxml.xsd
    // But MusicXML can be zipped, and in that case has not the `+xml`, even if the content is the same.
    'http://www.musicxml.org/xsd/MusicXML'              => 'application/vnd.recordare.musicxml+xml',
    'http://www.openarchives.org/OAI/2.0/'              => 'application/vnd.openarchives.oai-pmh+xml',
    'http://www.openarchives.org/OAI/2.0/static-repository' => 'application/vnd.openarchives.oai-pmh+xml',
    // 'http://www.tei-c.org/ns/1.0'                    => 'application/vnd.tei+xml', // Deprecated in 2011.
    'http://www.tei-c.org/ns/1.0'                       => 'application/tei+xml',
    // 3D formats.
    'http://www.collada.org/2005/11/COLLADASchema'      => 'model/vnd.collada+xml',
    // Omeka should support itself.
    'http://omeka.org/schemas/omeka-xml/v1'             => 'text/vnd.omeka+xml',
    'http://omeka.org/schemas/omeka-xml/v2'             => 'text/vnd.omeka+xml',
    'http://omeka.org/schemas/omeka-xml/v3'             => 'text/vnd.omeka+xml',
    'http://omeka.org/schemas/omeka-xml/v4'             => 'text/vnd.omeka+xml',
    'http://omeka.org/schemas/omeka-xml/v5'             => 'text/vnd.omeka+xml',

    // Doctype and root elements in case there is no namespace.

    // 'alto'                                           => 'application/vnd.alto+xml', // Deprecated in 2017.
    'alto'                                              => 'application/alto+xml',
    'atom'                                              => 'application/atom+xml',
    'ead'                                               => 'application/vnd.ead+xml',
    'feed'                                              => 'application/atom+xml',
    'html'                                              => 'text/html',
    'mag'                                               => 'application/vnd.iccu.mag+xml',
    'mei'                                               => 'application/vnd.mei+xml',
    // 'mets'                                           => 'application/vnd.mets+xml', // Deprecated in 2011.
    'mets'                                              => 'application/mets+xml',
    // 'mods'                                           => 'application/vnd.mods+xml', // Deprecated in 2011.
    'mods'                                              => 'application/mods+xml',
    'pdf2xml'                                           => 'application/vnd.pdf2xml+xml', // Used in module IIIF Search.
    'refNum'                                            => 'application/vnd.bnf.refnum+xml',
    'rss'                                               => 'application/rss+xml',
    'score-partwise'                                    => 'application/vnd.recordare.musicxml+xml',
    'svg'                                               => 'image/svg+xml',
    // 'TEI'                                            => 'application/vnd.tei+xml', // Deprecated in 2011.
    'TEI'                                               => 'application/tei+xml',
    // 'collection'                                     => 'application/marcxml+xml',
    'xhtml'                                             => 'application/xhtml+xml',
];
