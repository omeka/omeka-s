# Omeka S

Omeka S is a web publication system for universities, galleries, libraries,
archives, and museums. It consists of a local network of independently curated exhibits sharing
a collaboratively built pool of items, media, and their metadata.

## Installation

### Requirements
* Linux
* Apache
* MySql 5.5.3+ and the MySQL driver for PDO
* PHP 5.5.2+ (the latest stable version preferred) and the PHP extensions for PDO

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
1. In your web browser, navigate to the omeka-s/install directory, where you can
   complete installation.

### Installing from released zip file

1. Download the latest release from the [release page](https://github.com/omeka/omeka-s/releases)
1. Open `config/database.ini` and add your MySQL username, password, database
   name, and host name. The user and database must be created before this step.
1. Make sure the `files/` directory is writable by Apache.
1. In your web browser, navigate to the omeka-s/install directory, where you can
   complete installation.
   
You can find Omeka-specific code under `application/`.

## Libraries

Omeka uses the following libraries, among others:

* [Zend Framework 2](http://framework.zend.com/)
* [Doctrine 2](http://www.doctrine-project.org/)
* [EasyRdf](http://www.easyrdf.org/)
* [PHPUnit](https://phpunit.de/)
* [jQuery](http://jquery.com/)

## Coding Standards

Omeka development adheres to the [Zend Framework 2 Coding Standards](https://zf2-docs.readthedocs.org/en/latest/ref/coding.standard.html) 
and uses the [git-flow](http://nvie.com/posts/a-successful-git-branching-model/) branching model.
