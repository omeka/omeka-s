Omeka S Addons
==============

Add-ons are modules and themes managed by composer when they have the type
`omeka-s-module` or `omeka-s-theme`.

The use of add-ons allows to use a single command `composer require xxx/yyy`
to manage an Omeka instance, to avoid duplication of dependencies, and to
improve speed of Omeka init. With composer, add-ons are installed automatically
under `composer-addons/modules/` and `composer-addons/themes/` and their own
dependencies are shared with the Omeka ones in `vendor/`.

Omeka S still supports classic locations `modules/` and `themes/`. When a module
or a theme with the same name is located in `modules/` or `themes/`, it is
prioritary and the composer add-on is skipped.

If a module has no composer.json file or is not available on
https://packagist.org, it still can be managed via composer via the key
`repositories` (see https://getcomposer.org/doc/04-schema.md#repositories).


Version compatibility
---------------------

Add-ons can require `omeka/omeka-s` to declare compatibility with a specific
Omeka version:

```json
{
    "require": {
        "omeka/omeka-s": "^4.0"
    }
}
```

Omeka S uses Composer's `branch-alias` mechanism to ensure version constraints
work on both tagged releases and development branches. The version constraint
is automatically checked at install time.

For manual add-ons (without composer), use `omeka_version_constraint` in
`config/module.ini` or `config/theme.ini`.


Extra keys
----------

The file composer.json supports optional specific keys under key `extra`:

- `installer-name`: directory to use when different from project name.
- `label`: display label when different from project name.

If an extra key is not available, a check is done for an equivalent in file
`config/module.ini` or `config/theme.ini`, if present, else a default value is
set.


Configurable modules
--------------------

To declare a module as configurable, add the key in `config/module.config.php`:

```php
return [
    'module_config' => [
        'configurable' => true,
    ],
    // ... other config
];
```

This takes precedence over `config/module.ini` (fallback for legacy modules).


External assets
---------------

Modules and themes that need external js/css/fonts/img libraries can use another
composer plugins like [sempia/external-assets](https://gitlab.com/sempia/composer-plugin-external-assets),
a lightweight solution, or [civicrm/composer-downloads-plugin](https://github.com/civicrm/composer-downloads-plugin),
a full featured tool (variables, ignore patterns, executable flag), or any other
one.

Example using `sempia/external-assets`:

```json
{
    "require": {
        "sempia/external-assets": "^1.0"
    },
    "extra": {
        "external-assets": {
            "asset/vendor/mirador/": "https://github.com/ProjectMirador/mirador/releases/download/v3.3.0/mirador.zip",
            "asset/vendor/lib/jquery.min.js": "https://cdn.example.com/jquery-3.7.0.min.js"
        }
    }
}
```


Manual installation
-------------------

For add-ons installed manually via `git clone` in directory `modules/` or
`themes/`, dependencies are not downloaded automatically. Use the following
script from the Omeka root:

```sh
# 1. Clone the add-on
git clone https://gitlab.com/user/MyModule modules/MyModule

# 2. Install composer dependencies (other modules, libraries)
php application/data/scripts/install-addon-deps.php MyModule
```

### Install dependencies

```sh
# Module
php application/data/scripts/install-addon-deps.php ModuleName

# Theme
php application/data/scripts/install-addon-deps.php --theme theme-name

# Preview without installing
php application/data/scripts/install-addon-deps.php --dry-run ModuleName
```


Funding
-------

This feature was funded for the [digital library Manioc](https://manioc.org) of
the [Université des Antilles](https://www.univ-antilles.fr) (subvention
Agence bibliographique de l'enseignement supérieur [Abes](https://abes.fr)).
