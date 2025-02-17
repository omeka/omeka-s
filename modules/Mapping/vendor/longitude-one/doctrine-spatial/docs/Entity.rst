Create spatial entities
=======================

It is a good practice to use the most adapted column to store you geometric or geographic data.
If your entity have only to store points, do not use a "geometry" type, but a "point" type.
Use a geometry column only if your entity can store different types (points and lines as example)

Example1: Entity with a spatial point
-------------------------------------

Below, you will find an example to declare an entity with a ``point``. Before you need to declare the point type as
described in the :doc:`configuration section <./Configuration>`.

.. code-block:: php

    <?php

    // We declare the class of the type we want to use.
    use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
    // We declare the Mapping as usual
    use Doctrine\ORM\Mapping as ORM;

    /**
     * Point entity example.
     *
     * As you can see we do not change anything in Entity and Table annotations. Feel free to use them as usual.
     *
     * @ORM\Entity
     * @ORM\Table
     */
    class PointEntity
    {
        /**
         * @var int The usual Doctrine Identifier
         *
         *  As you can see we do not change anything in Entity and Table annotations. Feel free to use it as usual.
         *
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         * @ORM\Column(type="integer")
         */
        protected $id;

        /**
         * @var Point
         *
         * As you can see we declare a point property of type point.
         * point shall be declared in the doctrine.yaml as a custom type.
         * Feel free to use options as usual. As example, I declared that point is not nullable. But you can
         * set it to nullable=true if you want.
         *
         * @ORM\Column(type="point", nullable=false)
         */
        protected $point;

        /**
         * Get id.
         * This is the usual Id getter.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Get point.
         * This is a standard getter.
         *
         * @return Point
         */
        public function getPoint(): Point
        {
            return $this->point;
        }

        /**
         * Set point.
         * This is a fluent setter.
         *
         * @param Point $point point to set
         *
         * @return self
         */
        public function setPoint(Point $point): self
        {
            $this->point = $point;

            return $this;
        }
    }

Seven examples with each geometric spatial types
---------------------------------------------------------------

The `Fixtures directory`_ creates some spatial entities for our tests. Inside this directory, you will find a lot of 
entities which are implementing geometric properties:

* Entity with a `geometric`_ type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/NoHintGeometryEntity.php>`
* Entity with a `geometric linestring`_ type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoLineStringEntity.php>`
* Entity with a `geometric multilinestring`_  type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/MultiLineStringEntity.php>`
* Entity with a `geometric multipoint`_  type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/MultiPointEntity.php>`
* Entity with a `geometric multipolygon`_ type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/MultiPolygonEntity.php>`
* Entity with a `geometric point`_ type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/PointEntity.php>`
* Entity with a `geometric polygon`_ type. :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/PolygonEntity.php>`

Four examples with each geographic spatial types
---------------------------------------------------------------

The `Fixtures directory`_ creates some spatial entities for our tests. Inside this directory, you will find a lot of
entities which are implementing geographic properties:

* Entity with a `geographic`_ type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeographyEntity.php>`
* Entity with a `geographic linestring`_ type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoLineStringEntity.php>`
* Entity with a `geographic point`_  type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoPointSridEntity.php>`
* Entity with a `geographic polygon`_  type, :download:`download <https://raw.githubusercontent.com/longitude-one/doctrine-spatial/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoPolygonEntity.php>`

.. _Fixtures directory: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures
.. _geographic: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeographyEntity.php
.. _geographic linestring: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoLineStringEntity.php
.. _geographic point: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoPointSridEntity.php
.. _geographic polygon: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoPolygonEntity.php
.. _geometric: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/NoHintGeometryEntity.php
.. _geometric linestring: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/GeoLineStringEntity.php
.. _geometric multilinestring: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/MultiLineStringEntity.php
.. _geometric multipoint: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/MultiPointEntity.php
.. _geometric multipolygon: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/MultiPolygonEntity.php
.. _geometric point: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/PointEntity.php
.. _geometric polygon: https://github.com/longitude-one/doctrine-spatial/tree/master/tests/LongitudeOne/Spatial/Tests/Fixtures/PolygonEntity.php
