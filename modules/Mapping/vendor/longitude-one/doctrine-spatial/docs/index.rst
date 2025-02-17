.. Doctrine spatial extension documentation master file, created by Alexandre Tranchant

Welcome to Doctrine spatial extension's documentation!
######################################################

Doctrine spatial extension provides spatial types and spatial functions for doctrine. It allows you to manage
spatial entity and to store them into your database server.

Currently, doctrine spatial extension provides two dimension general geometric and geographic spatial types,
two-dimension points, linestrings, polygon and two-dimension multi-points, multi-linestrings, multi-polygons. Doctrine
spatial is only compatible with MySql and PostgreSql. For better security and better resilience of your spatial data,
we recommend that you favor the PostgreSql database server because of `the shortcomings and vulnerabilities of MySql`_.

This project was initially created by Derek J. Lambert in 2015. In March 2020, Alexandre Tranchant forked the originally
project because of unactivity for two years. Feel free to :doc:`contribute <./Contributing>`. Any help is welcomed:

* to implement third and fourth dimension in spatial data,
* to implement new spatial function,
* to complete documentation and fix typos, *(I'm not fluent in english)*
* to implement new abstracted platforms like Microsoft Sql Server.

Contents
********

.. toctree::
   :maxdepth: 5

   Installation
   Configuration
   Entity
   Repository
   Glossary
   Contributing
   Test

.. _the shortcomings and vulnerabilities of MySql: https://sqlpro.developpez.com/tutoriel/dangers-mysql-mariadb/