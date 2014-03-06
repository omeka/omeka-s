# Omeka 3 (Multisite)

## Installation

1. Clone this repository in your Apache web directory:
   * `$ git clone git@github.com:omeka/Omeka3.git`
1. Change into the Omeka3 directory:
   * `$ cd Omeka3`
1. Install [Composer](http://getcomposer.org/): 
   * `$ curl -sS https://getcomposer.org/installer | php`
1. Install dependencies using Composer: 
   * `$ ./composer.phar install`
1. Copy and rename the htaccess, local config, and database config files: 
   * `$ cp .htaccess.dist .htaccess`
   * `$ cp config/local.config.php.dist config/local.config.php`
   * `$ cp config/database.ini.dist config/database.ini`
1. Open `config/database.ini` and add your MySQL username, password, database
   name, and host name.
1. In your web browser, navigate to the Omeka3/install directory, where you can
   complete installation.

You can find Omeka-specific code under module/Omeka.

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
