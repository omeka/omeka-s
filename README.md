# Omeka S (Multisite)

The Omeka S web publication system for universities, galleries, libraries, archives, and museums. A local network of independently
curated exhibits sharing a collaboratively built pool of items and their
metadata. 

## Installation

1. Clone this repository in your Apache web directory:
   * `$ git clone git@github.com:omeka/omeka-s.git`
1. Change into the Omeka S directory:
   * `$ cd omeka-s`
1. Perform first-time setup:
   * `$ ant init`
1. Open `config/database.ini` and add your MySQL username, password, database
   name, and host name.
1. In your web browser, navigate to the omeka-s/install directory, where you can
   complete installation.

You can find Omeka-specific code under application/Omeka.

## Libraries Used

Omeka uses the following libraries

* [Zend Framework 2](http://framework.zend.com/)
* [Doctrine](http://www.doctrine-project.org/)
* [Composer](http://getcomposer.org/)
* [jQuery](http://jquery.com/)
* [Symfony Console](http://symfony.com/doc/current/components/console/introduction.html)

## Coding Standards

Omeka development adheres to the [Zend Framework 2 Coding Standards](http://framework.zend.com/wiki/display/ZFDEV2/Coding+Standards) 
and uses the [git-flow](http://nvie.com/posts/a-successful-git-branching-model/) branching model.
