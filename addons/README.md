Omeka S Addons
==============

Add-ons are modules and themes managed by composer when they have the type
`omeka-s-module` or `omeka-s-theme`.

The use of add-ons allows to use a single command `composer require xxx/yyy`
to manage an Omeka instance, to avoid duplication of dependencies, and to
improve speed of Omeka init. With composer, add-ons are installed automatically
under `addons/modules/` and `addons/themes/` and their own dependencies are
shared with the Omeka ones in `vendor/`.

Omeka S still supports classic locations `modules/` and `themes/`. When a module
or a theme with the same name is located in `modules/` or `themes/`, it is
prioritary and the composer add-on is skipped.

If a module has no composer.json file or is not available on https://packagist.org,
it still can be managed via composer via the key `repositories` (see
https://getcomposer.org/doc/04-schema.md#repositories).


Extra keys
----------

The file composer.json supports optional specific keys under key `extra`:

- `installer-name`: directory to use when different from project name.
- `label`: display label when different from project name.
- `addon-version`: version of add-on for Omeka, else extracted from composer.
- `omeka-version-constraint`: limit compatibility with a specific Omeka version.
- `standalone`: boolean to specify to use own module directory `vendor/`.
- `configurable`: boolean to specify if the module is configurable.

If an extra key is not available, a check is done for an equivalent in file
`config/module.ini` or `config/theme.ini`, if present, else a default value is
set.

For assets (libraries for css/img/js/fonts/etc.), no central directory is
defined for now. Each module can manage them as they want, for example in `asset/vendor/`,
with or without composer. It is recommended not to use nodejs to install them to
be consistent with Omeka, that should be manageable on a server without nodejs.


Funding
-------

This feature was funded for the [digital library Manioc](https://manioc.org) of
the [Université des Antilles](https://www.univ-antilles.fr) (subvention
Agence bibliographique de l’enseignement supérieur [Abes](https://abes.fr)).
