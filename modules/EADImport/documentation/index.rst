
Introduction
============

How does it work ?
------------------

This Omeka S module allows to import resources from xml file "ead" formatted

Where is the configuration
--------------------------

Strictly speaking, there's no configuration required for the module, which is mapped directly when a new import is made. For your information, the submitted file will be parsed to process only `<c>` tags with a "level" attribute. Each tab will represent a different "level" and the nodes appearing will be those found in the hard-coded list in the module (EAD-2002-Paths_). "Parent-child" links are made automatically and are directly linked to the dcterms:hasPart and dcterms:isPartOf properties.

.. _EAD-2002-Paths: https://github.com/biblibre/omeka-s-module-EADImport/blob/master/src/NodeXpaths/EAD2002Xpath.php

.. toctree::
   :maxdepth: 2
   :caption: Contents

   configuration
   features
   interface-with-other-modules
   tutorials
