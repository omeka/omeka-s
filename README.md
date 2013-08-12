# Omeka 3 (Multisite)

## Installation

1. Install [Composer](http://getcomposer.org/): `$ curl -sS https://getcomposer.org/installer | php`
2. Install the [Doctrine](http://www.doctrine-project.org/) environment: `$ ./composer.phar install`
3. Create the Omeka database: `$ php vendor/bin/doctrine orm:schema-tool:create`
4. Include bootstrap.php in your own script and use the `$em` entity manager to 
   work with Omeka's ORM. See Doctrine's [documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html).
