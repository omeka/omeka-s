# Omeka S

The Omeka S web publication system for universities, galleries, libraries,
archives, and museums. A local network of independently curated exhibits sharing
a collaboratively built pool of items and their metadata.

## Installation

1. Clone this repository in your Apache web directory:
   * `$ git clone git@github.com:omeka/omeka-s.git`
1. Change into the Omeka S directory:
   * `$ cd omeka-s`
1. Perform first-time setup:
   * `$ ant init`
1. Open `config/database.ini` and add your MySQL username, password, database
   name, and host name.
1. Generate proxy files needed by Doctrine (one time only)
   * `$ ant generate-proxies`
1. In your web browser, navigate to the omeka-s/install directory, where you can
   complete installation.

You can find Omeka-specific code under application/.

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
