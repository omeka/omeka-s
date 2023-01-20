<?php

namespace Omeka\I18n;

use Laminas\I18n\Translator\Loader\Gettext;
use Laminas\I18n\Translator\TextDomain;

/**
 * Subclass of the default Laminas gettext translation loader
 *
 * The change performed is that the declared plural rule for the gettext
 * file is discarded, if the file contained no plural translations.
 *
 * Plural mismatches between translation files lead to an immediate exception
 * when Laminas tries to merge them. By discarding the plurals, this situation
 * will only arise if there's a mismatch between two actually-plural-using
 * modules, excluding the majority that don't use plural translations.
 */
class GettextLoader extends Gettext
{
    public function load($locale, $filename)
    {
        $textDomain = parent::load($locale, $filename);

        $hasPlurals = false;
        foreach ($textDomain as $string) {
            if (is_array($string)) {
                $hasPlurals = true;
                break;
            }
        }
        if ($hasPlurals) {
            return $textDomain;
        } else {
            // TextDomain interface doesn't allow setting plural rule back to
            // null so we'll just make a new one
            $depluralizedTextDomain = new TextDomain;
            $depluralizedTextDomain->exchangeArray($textDomain->getArrayCopy());
            return $depluralizedTextDomain;
        }
    }
}
