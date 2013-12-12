# Omeka 3 (Multisite)

## Installation

1. Clone this repository in your Apache web directory:
   `$ git clone git@github.com:omeka/Omeka3.git`
1. Change into the Omeka3 directory:
   `$ cd Omeka3`
1. Install [Composer](http://getcomposer.org/): 
   `$ curl -sS https://getcomposer.org/installer | php`
1. Install dependencies: 
   `$ ./composer.phar install`
1. Copy and rename the application config file: 
   `$cp config/application.config.php.dist config/application.config.php`
1. Open config/application.config.php and add your MySQL username, password, and 
   database name.
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
