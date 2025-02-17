Contributing
************

Documentation
=============

This documentation is done with sphinx. All documentation are stored in the ``docs`` directory. To contribute to this
documentation (and fix the lot of typo), you could install docker and start the docker service named ``spatial_doc``.
It's included in the repository. It will self-compile the documentation. After you start this service, you can now
access the documentation at http://localhost:8100. If you try changing any ``rst`` file, after some seconds the browser
auto refresh to show the updated documentation. Following the Sphinx documentation site you can now document this
project using Sphinx.


1. Edit files in the ``docs`` directory,
2. Verify that documentation is improved,
3. Commit your contribution with an explicit message,
4. Push your commit and create a pull request to the longitude-one/doctrine-spatial project.

Source code
===========

How to create a new function?
-----------------------------

It's pretty easy to create a new function. A few lines code are sufficient.

Where to store your class?
^^^^^^^^^^^^^^^^^^^^^^^^^^
If your function is described in the `OGC Standards`_ or in the `ISO/IEC 13249-3`_, the class implementing the function
**shall** be create in the lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/`Standard directory`_.

If your spatial function is not described in the OGC Standards nor in the ISO, your class should be prefixed by Sp
(specific). If your class is specific to MySql, you shall create it in the
lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/`MySql directory`_.
If your class is specific to PostgreSQL, you shall create it in the
lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/`PostgreSql directory`_.
If your class is not described in the OGC Standards nor in the ISO norm, but exists in MySQL and in PostgreSQL, accepts
the same number of arguments and returns the same results (which is rarely the case), then you shall create it in the
lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/`Common directory`_.

Which name for your function?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Create a new class. It's name shall be the same than the function name in camel case prefixed with ``St`` or ``Sp``.
The standards are alive, they can be updated at any time. Regularly, new spatial function are defined by consortium. So,
to avoid that a new standardized function as the same name from an existing function, the ``St`` prefix is reserved to
already standardized function.

If your function is described in the `OGC Standards`_ or in the `ISO/IEC 13249-3`_, the prefix shall be ``St`` else your
class shall be prefixed with ``Sp``.
As example, if you want to create the spatial ``ST_Z`` function, your class shall be named ``StZ`` in the
`Standard directory`_.
If you want to create the `ST_Polygonize`_ PostgreSql function which is not referenced in the OGC nor in ISO,
then you shall name your class ``SpPolygonize`` and store them in the `PostgreSql directory`_.

Which method to implements?
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Now you know where to create your class, it should extends ``AbstractSpatialDQLFunction`` and you have to implement four
functions:

1. ``getFunctionName()`` shall return the SQL function name,
2. ``getMaxParameter()`` shall return the maximum number of arguments accepted by the function,
3. ``getMinParameter()`` shall return the minimum number of arguments accepted by the function,
4. ``getPlatforms()`` shall return an array of each platform accepting this function.

As example, if the new spatial function exists in PostgreSQL and in MySQL, ``getPlatforms()`` should be like this:

.. code-block:: php

    <?php

    // ...

    /**
     * Get the platforms accepted.
     *
     * @return string[] a non-empty array of accepted platforms
     */
    protected function getPlatforms(): array
    {
        return ['postgresql', 'mysql'];
    }

Do not hesitate to copy and paste the implementing code of an existing spatial function.

If your function is more specific and need to be parse, you can overload the parse method.
The PostgreSQL `SnapToGrid`_ can be used as example.

All done! Your function is ready to used, but, please, read the next section to implement tests.

Don't forget to check your code respect our standard of quality:

.. code-block:: bash

    docker exec spatial-php7 composer check-quality-code

How to test your new function?
------------------------------

Please, create a functional test in the same way. You have a lot of example in the `functions test directory`_.

Setup
^^^^^

Here is an example of setup, each line is commented to help you to understand how to setup your test.

.. code-block:: php

    <?php

    use LongitudeOne\Spatial\Exception\InvalidValueException;
    use LongitudeOne\Spatial\Exception\UnsupportedPlatformException;
    use LongitudeOne\Spatial\Tests\Helper\PointHelperTrait;
    use LongitudeOne\Spatial\Tests\OrmTestCase;
    use Doctrine\DBAL\Exception;
    use Doctrine\ORM\Exception\ORMException;

    /**
     * Foo DQL functions tests.
     * These tests verify their implementation in doctrine spatial.
     *
     * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
     * @license https://alexandre-tranchant.mit-license.org MIT
     *
     * Please preserve the three above annotation.
     *
     * Group is used to exclude some tests on some environment.
     * Internal is to avoid the use of the test outer of this library
     * CoversDefaultClass is to avoid that your test covers other class than your new class
     *
     * @group dql
     *
     * @internal
     * @coversDefaultClass
     */
    class SpFooTest extends OrmTestCase
    {
        // To help you to create some geometry, I created some Trait.
        // use it to be able to call some methods which will store geometry into your database
        // In this example, we use a trait that will create some points.
        use PointHelperTrait;

        /**
         * Setup the function type test.
         */
        protected function setUp(): void
        {
            //If you create point entity in your test, you shall add the line above or the **next** test will failed
            $this->usesEntity(self::POINT_ENTITY);
            //If the method exists in mysql, You shall test it. Comment this line if function does not exists on MySQL
            $this->supportsPlatform('mysql');
            //If the method exists in postgresql, You shall test it. Comment this line if function does not exists on PostgreSql
            $this->supportsPlatform('postgresql');

            parent::setUp();
        }

        /**
         * Test a DQL containing function to test in the select.
         */
        public function testSelectSpBuffer()
        {
            //The above protected method come from the point helper trait.
            //It creates a point at origin (0 0) and persist it in database
            $pointO = $this->persistPointOrigin();

            //We create a query using your new DQL function SpFoo
            $query = $this->getEntityManager()->createQuery(
                'SELECT p, ST_AsText(SpFoo(p.point, :p) FROM LongitudeOne\Spatial\Tests\Fixtures\PointEntity p'
            );
            //Optionnaly, you can use parameter
            $query->setParameter('p', 'bar', 'string');
            //We retrieve the result
            $result = $query->getResult();

            //Now we test the result
            static::assertCount(1, $result);
            static::assertEquals($pointO, $result[0][0]);
            static::assertSame('POLYGON((-4 -4,4 -4,4 4,-4 4,-4 -4))', $result[0][1]);
        }

Now, open the `OrmTestCase.php file`_] and declare your function in one of this three methods:

* ``addStandardFunctions``
* ``addMySqlFunctions``
* ``addPostgreSqlFunctions``


You can launch the test. This :doc:`document <./Test>` helps you how to config your dev environment.
Please do not forgot to update documentation by adding your function in one of these three tables:

* :ref:`Standard functions`
* :ref:`Specific MySql functions`
* :ref:`Specific PostGreSQL functions`

Quality of your code
====================

Quality of code is auto-verified by php-cs-fixer, php code sniffer and php mess detector.

Before a commit, launch the quality script:

.. code-block:: bash

    docker spatial-php8 composer check-quality-code

You can launch PHPCS-FIXER to fix errors with:

.. code-block:: bash

    docker spatial-php8 composer phpcsfixer

You can launch PHP Code Sniffer only with:
.. code-block:: bash

    docker spatial-php8 composer phpcs

You can launch PHP Mess Detector only with:

.. code-block:: bash

    docker spatial-php8 composer phpmd

.. _Common directory: https://github.com/longitude-one/doctrine-spatial/tree/master/lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/Common
.. _MySql directory: https://github.com/longitude-one/doctrine-spatial/tree/master/lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/MySql
.. _PostgreSql directory: https://github.com/longitude-one/doctrine-spatial/tree/master/lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/PostgreSql
.. _Standard directory: https://github.com/longitude-one/doctrine-spatial/tree/master/lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/Standard
.. _ISO/IEC 13249-3: https://www.iso.org/standard/60343.html
.. _OGC standards: https://www.ogc.org/standards/sfs
.. _ST_Polygonize: https://postgis.net/docs/manual-2.5/ST_Polygonize.html
.. _SnapToGrid: https://github.com/longitude-one/doctrine-spatial/tree/master/lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/PostgreSql/SpSnapToGrid.php
.. _functions test directory: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/ORM/Query/AST/Functions/
.. _OrmTestCase.php file: https://github.com/longitude-one/doctrine-spatial/blob/master/tests/LongitudeOne/Spatial/Tests/OrmTestCase.php
