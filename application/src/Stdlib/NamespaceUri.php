<?php
namespace Omeka\Stdlib;

/**
 * The forms of a namespace URI that name the same vocabulary: ending in "#",
 * "/", or neither, with http or https. For example, http://schema.org/ and
 * https://schema.org/ are forms of one namespace URI.
 */
class NamespaceUri
{
    /**
     * Get a namespace URI's forms: itself, then ending in "#", "/", and
     * neither, each with http and https.
     *
     * @param string $namespaceUri
     * @return string[]
     */
    public static function getForms($namespaceUri)
    {
        $namespaceUri = (string) $namespaceUri;
        $base = rtrim($namespaceUri, '#/');
        $forms = [$namespaceUri, $base . '#', $base . '/', $base];
        foreach ($forms as $form) {
            if (0 === strpos($form, 'http://')) {
                $forms[] = 'https://' . substr($form, 7);
            } elseif (0 === strpos($form, 'https://')) {
                $forms[] = 'http://' . substr($form, 8);
            }
        }
        return array_values(array_unique($forms));
    }
}
