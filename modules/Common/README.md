Common Module (module for Omeka S)
===================================


> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__


[Common Module] is a module for [Omeka S] that allows to manage internal
features used in various modules: bulk functions, form elements, view helpers,
one-time tasks for install and settings, etc., so it avoids the developer to
copy-paste common code between modules.

- View helpers

  - AssetUrl for internal assets
  - EasyMeta to get ids, terms and labels from properties, classes, templates,
    vocabularies; to get main data types too (literal, resource or uri); to get
    resource api names from any names used in Omeka and modules.

- Form elements

  - Array Text
  - Custom Vocabs Select
  - Data Textarea
  - Data Type Select
  - Group Textarea
  - Ini Textarea
  - Media Ingester Select
  - Media Renderer Select
  - Media Type Select
  - Sites Page Select
  - Optional Checkbox
  - Optional Date
  - Optional DateTime
  - Optional Multi Checkbox
  - Optional Number
  - Optional Radio
  - Optional Select
  - Optional Url
  - Optional Item Set Select
  - Optional Property Select
  - Optional Resource Select
  - Optional Resource Class Select
  - Optional Resource Template Select
  - Optional Role Select
  - Optional Site Select
  - Optional User Select
  - Url Query

- [PSR-3]

  - The logger can log messages in a standard, simple and translatable way.
  - The class PsrMessage allows to use PSR-3 messages and is compliant with
    C-style messages (with sprintf: %s, %d, etc.).

- One-Time tasks

  Internally, the logic is "config over code": so all settings have just to be
  set in the main `config/module.config.php` file, inside a key with the
  lowercas emodule name, with sub-keys `config`, `settings`, `site_settings`,
  `user_settings` and `block_settings`. All the forms have just to be standard
  Laminas forms.

  Eventual install and uninstall sql can be set in `data/install/` and upgrade
  code in `data/scripts`. Another class allows to check and install resources
  (vocabularies, resource templates, custom vocabs, etc.).

- Improved media type detection

  In many cases, in particular with xml or json, the media type should be
  refined to make features working. For example `text/xml` is not precise enough
  for the module IiifServer to manage xml ocr alto files, that should be
  identified with the right media type `application/alto+xml`. The same issue
  occurs with xml mets, tei, json-ld, etc.


Installation
------------

See general end user documentation for [installing a module].

* From the zip

Download the last release [Common.zip] from the list of releases, and
uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `Common`.

Then install it like any other Omeka module and follow the config instructions.


Usage (for developer)
---------------------

### PSR-3

#### Role of PSR-3

The PHP Framework Interop Group ([PHP-FIG]) represents the majority of php
frameworks, in particular all main CMS.

[PSR-3] means that the message and its context may be separated in the logs, so
they can be translated and managed by any other compliant tools. This is useful
in particular when an external database is used to store logs.

The message uses placeholders that are not in C-style of the function `sprintf`
(`%s`, `%d`, etc.), but in moustache-style, identified with `{` and `}`, without
spaces.

The new and old format are automatically managed by the messenger and the
logger.

So, instead of logging like this:

```php
// Classic logging (not translatable).
$this->logger()->info(sprintf($message, ...$args));
$this->logger()->info(sprintf('The %s #%d has been updated.', 'item', 43));
// output: The item #43 has been updated.
```

A PSR-3 standard log is:

```php
// PSR-3 logging.
$this->logger()->info($message, $context);
$this->logger()->info(
    'The {resource} #{id} has been updated.', // @translate
    ['resource' => 'item', 'id' => 43]
);
// output: The item #43 has been updated.
```

If an Exception object is passed in the context data, it must be in the `exception`
key.

Because the logs are translatable at user level, with a message and context, the
message must not be translated when logging.

#### Helpers

- PSR-3 Message

If the message may be reused or for the messenger, the helper `PsrMessage()` can
be used, with all the values:

```php
// For logging, it is useless to use PsrMessage, since it is natively supported
// by the logging.
$message = new \Common\Stdlib\PsrMessage(
    'The {resource} #{id} has been updated by user #{userId}.', // @translate
    ['resource' => 'item', 'id' => 43, 'userId' => $user->id()]
);
$this->logger()->info($message->getMessage(), $message->getContext());
echo $message;
// With translation.
echo $message->setTranslator($translator);
```

- Messages

The method `getTranslatedMessages()` allows to get all translated messages as
array. It can be used for a json output.

The method `log()` allows to convert all messages into logs, for example to
manage background jobs and keep track of front-end messages of errors of some
modules.

- Messenger

A form with any level of sub-messages can be managed.

- Plugin jSend

The plugin jSend() allows to output a JsonModel formatted as [jSend] to simplify
exchanges with a third party.

### Translator

The translator to set in PsrMessage() is available through `$this->translator()`
in controller and view.

#### Compatibility

* Compatibility with messenger

The helper `messenger()` is compatible and can translate PSR-3 messages.

* Compatibility with the default stream logger

The PSR-3 messages are converted into simple messages for the default logger.
Other extra data are appended.

* Compatibility with core messages

The logger stores the core messages as it, without context, so they can be
displayed. They are not translatable if they use placeholders.

* Compatibility with thrown exceptions

An exception should not be translated early. Nevertheless, if you really need
it, you can use:

```php
# Where `$this->translator` is the MvcTranslator from services, either:
throw new \RuntimeException($this->translator->translate($message));
throw new \Exception($message->setTranslator($this->translator)->translate());
```

#### Plural

By construction, the plural is not managed: only one message is saved in the
log. So, if any, the plural message should be prepared before the logging.

### One-time tasks

Unlike old module Generic, there are two ways to get the one-time features
inside any module: the trait (recommended) or the abstract class (deprecated).

To use them, replace the following:

```php
namespace MyModule;

use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
}
```

with this class with the trait:

```php
namespace MyModule;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    use TraitModule;
}
```

Or extend the abstract class (not recommended):

```php
if (!class_exists(\Common\AbstractModule::class)) {
    require_once dirname(__DIR__) . '/Common/AbstractModule.php';
}

use Common\AbstractModule;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;
}
```

**WARNING**: with an abstract class, `parent::method()` in the module calls the
method of the abstract class (`Common\AbstractModule`), but with a trait,
`parent::method()` is the method of `Omeka\AbstractModule` if it exists.
Furthermore, it is not possible to call a method of the trait that is overridden
by the class Module. This is why there are methods suffixed with "Auto" that can
be used in such a case.

### Installing resources

To install resources, the class `ManageModuleAndResources.php` can be used. It
is callable via the module `$this->getManageModuleAndResources()`. It contains
tools to manage and update vocabs, custom vocabs, and templates via files
located inside `data/`, that will be automatically imported.


TODO
----

- [ ] Use key "psr_log" instead of "log" (see https://docs.laminas.dev/laminas-log/service-manager/#psrloggerabstractadapterfactory).
- [ ] Use materialized views for EasyMeta?


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Copyright
---------

* Copyright Daniel Berthereau, 2017-2025 (see [Daniel-KM] on GitLab)


[Common module]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[Omeka S]: https://omeka.org/s
[GitLab]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[PSR-3]: http://www.php-fig.org/psr/psr-3
[PHP-FIG]: http://www.php-fig.org
[installing a module]: https://omeka.org/s/docs/user-manual/modules/
[Common.zip]: https://github.com/Daniel-KM/Omeka-S-module-Common/releases
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://opensource.org/licenses/MIT
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
