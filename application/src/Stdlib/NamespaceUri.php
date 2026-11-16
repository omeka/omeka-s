<?php
namespace Omeka\Stdlib;

/**
 * The forms a vocabulary's namespace URI is written in: ending in "#", "/",
 * or neither, with http or https. A vocabulary that moved from http to
 * https, or is written with a closing "/" in one place and without it in
 * another, is the same vocabulary in each form.
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
