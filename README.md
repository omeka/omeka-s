# Omeka S

Omeka S is a web publication system for universities, galleries, libraries,
archives, and museums. It consists of a local network of independently curated exhibits sharing
a collaboratively built pool of items, media, and their metadata.

## Installation

### Requirements
* Linux
* Apache
* MySql 5.5.3+ and the MySQL driver for PDO
* PHP 5.6+ (the latest stable version preferred) and the PHP extensions for PDO

### Gotchas
* The default library for generating thumbnails is ImageMagick, at least version 6.7.5. Older versions will not correctly produce thumbnails. See local.config.php options below. 

### Installing from GitHub

1. Clone this repository in your Apache web directory:
   * `$ git clone https://github.com/omeka/omeka-s.git`
1. Change into the Omeka S directory:
   * `$ cd omeka-s`
1. Perform first-time setup:
   * `$ ant init`
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

1. `git pull` as usual. Before the official release, latest code will be on branch 'develop'.
2. From the Omeka S root directory, run `ant install-deps` to make sure dependencies are up to date.
3. Compare changes in `/config/local.config.php` and `/config/local.config.php.dist`. Some default configurations might have changed, so you might need to reconcile changes to the distributed configuration with your local configuration (e.g., a path to PHP specific to your server, dev mode settings, etc.)
4. In your web browser, go to your site and run any migrations that are needed.

### Updating from released zip file
1. Download the latest release from the [release page](https://github.com/omeka/omeka-s/releases)
2. Make a copy of your `/config/local.config.php` file.
3. Make a copy of your `/modules` and `/themes` directories.
4. Make a copy of your `/files` directory.
5. Remove all Omeka S files, and replace them with the files from the updated zip file.
6. Replace your original `/config/local.config.php` file, and the `/modules`, `/themes`, and `/files` directories that you copied.
7. In your web browser, go to your site and run any migrations that are needed.

## local.config.php options

* `thumbnailer` Default is `Omeka\File\ImageMagickThumbnailer`. Also available are `Omeka\File\IMagickThumbnailer` and `Omeka\File\GdThumbnailer`
* `phpcli_path` Default is to attempt to detect correct path to PHP. Use this option to specify a path if needed in your server configuration. For example: 
```
    'cli' => array(
        'phpcli_path' => '/usr/bin/php55',
    ),

``` 


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
