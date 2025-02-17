Test environment
================

If you want to contribute to this library, you're welcome. This section will help you to prepare your development
environment.

How to prepare environment?
---------------------------

Doctrine library is available for MySQL and PostGreSQL.

1. [Install docker](https://docs.docker.com/engine/install/),
2. Go to the docker directory and start docker

.. code-block:: bash

    cd <project_directory>
    cd docker
    docker-compose up -d
    cd ..

Done! Your environment is ready with five services:

1. A MySQL5.7 service, you can connect to database with mysql://main@main:127.0.0.1:3357/main/
2. A MySQL8.0 service, you can connect to database with mysql://main@main:127.0.0.1:3380/main
3. A PostGreSQL service, you can connect to database with mysql://main@main:127.0.0.1:5432/main
4. A PHP7.4 service, you can test it via: docker exec spatial-php7 php -v
5. A PHP8.0 service, you can test it via: docker exec spatial-php8 php -v

Composer is installed on spatial-php7 and spatial-php8.

.. code-block:: bash

    docker exec spatial-php7 composer -v
    docker exec spatial-php8 composer -v

How to start test?
--------------------------
Copy docker/phpunit.*.xml to the project directory

.. code-block:: bash

    cp docker/phpunit.*.xml .

Then, you can launch the test on php7, then php8:

.. code-block:: bash

    docker exec spatial-php7 composer test-mysql5.7
    docker exec spatial-php7 composer test-mysql8.0
    docker exec spatial-php7 composer test-pgsql
    docker exec spatial-php8 composer test-mysql5.7
    docker exec spatial-php8 composer test-mysql8.0
    docker exec spatial-php8 composer test-pgsql

After any update, before any commit, simply check your code with this composer command:

.. code-block:: bash

    docker exec spatial-php7 composer check-quality-code
