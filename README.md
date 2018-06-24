# Omeka S

Omeka S is a web publication system for universities, galleries, libraries,
archives, and museums. It consists of a local network of independently curated
exhibits sharing a collaboratively built pool of items, media, and their metadata.

See the [user manual](https://omeka.org/s/docs/user-manual) for more information.

## Installation

### Requirements
* Linux
* Apache (with [AllowOverride](https://httpd.apache.org/docs/2.4/mod/core.html#allowoverride) set to "All" and [mod_rewrite](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) enabled)
* MySql 5.5.3+
* PHP 5.6+ (latest stable version preferred, with [PDO](http://php.net/manual/en/intro.pdo.php), [pdo_mysql](http://php.net/manual/en/ref.pdo-mysql.php), and [xml](http://php.net/manual/en/intro.xml.php) extensions installed)

### Gotchas
* The default library for generating thumbnails is ImageMagick, at least version
6.7.5. Older versions will not correctly produce thumbnails. See local.config.php
options in the [documentation](https://omeka.org/s/docs/user-manual/configuration/).

### Installing from GitHub

1. Make sure [Node.js](https://nodejs.org/) and [npm](https://nodejs.org/) are installed
1. Clone this repository in your Apache web directory:
   * `$ git clone https://github.com/omeka/omeka-s.git`
1. Change into the Omeka S directory:
   * `$ cd omeka-s`
1. Perform first-time setup:
   * `$ npm install`
   * `$ npm install --global gulp-cli` (if you do not already have `gulp` installed)
   * `$ gulp init`
1. Open `config/database.ini` and add your MySQL username, password, database
   name, and host name. The user and database must be created before this step.
1. Make sure the `files/` directory is writable by Apache.
1. In your web browser, navigate to the omeka-s directory, where you can
   complete installation.

### Installing from released zip file

1. Download the latest release from the [release page](https://github.com/omeka/omeka-s/releases)
1. Open `config/database.ini` and add your MySQL username, password, database
   name, and host name. The user and database must be created before this step.
1. Make sure the `files/` directory is writable by Apache.
1. In your web browser, navigate to the omeka-s directory, where you can
   complete installation.

You can find Omeka-specific code under `application/`.

## Updating

*Make a backup copy of your entire site and its database!*

### Updating from GitHub

1. `git pull` as usual. Use the `master` branch for the latest releases.
2. From the Omeka S root directory, run `gulp deps` to make sure dependencies are up to date.
3. Compare changes in `/config/local.config.php` and `/config/local.config.php.dist`. Some default configurations might have changed, so you might need to reconcile changes to the distributed configuration with your local configuration (e.g., a path to PHP specific to your server, dev mode settings, etc.)
4. In your web browser, go to your site and run any migrations that are needed.

### Updating from released zip file
1. Download the latest release from the [release page](https://github.com/omeka/omeka-s/releases)
2. Make a copy of your `/config` directory. You will need to restore your `local.config.php` and `database.ini` files from that copy.
3. Make a copy of your `/modules` and `/themes` directories.
4. Make a copy of your `/files` directory.
5. Remove all Omeka S files, and replace them with the files from the updated zip file.
6. Replace your original `/config/local.config.php` file, and the `/modules`, `/themes`, and `/files` directories that you copied.
7. In your web browser, go to your site and run any migrations that are needed.

## Creating a zipped release

`gulp zip` will create a zipped version of Omeka S and store it in `/build`. Use the `--no-dev` flag to omit Composer
dev dependencies for a smaller package suitable for end-users. Official releases follow this same process from a
new, clean checkout.

## Libraries

Omeka uses the following libraries, among others:

* [Zend Framework 3](http://framework.zend.com/)
* [Doctrine 2](http://www.doctrine-project.org/)
* [EasyRdf](http://www.easyrdf.org/)
* [PHPUnit](https://phpunit.de/)
* [jQuery](http://jquery.com/)

## Coding Standards

Omeka development adheres to the [Zend Framework 2 Coding Standards](https://zf2-docs.readthedocs.org/en/latest/ref/coding.standard.html)
and uses the [git-flow](http://nvie.com/posts/a-successful-git-branching-model/) branching model.
