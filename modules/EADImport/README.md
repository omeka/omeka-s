# EAD Import

EAD Import is a module for [Omeka S](https://omeka.org/s/) which allows to
import resources from a XML file ("ead" formatted).

The complete documentation of EADImport can be found [here](https://biblibre.github.io/omeka-s-module-EADImport).

## Rationale

This module import resources from a XML file.

## Requirements

* Omeka S >= 3.0.0

## Quick start

1. [Add the module to Omeka S](https://omeka.org/s/docs/user-manual/modules/#adding-modules-to-omeka-s)
2. Login to the admin interface, and use it.

## Features

To begin, submit your XML file on new import form. You have the possibility to verify the structure by comparing with `EAD2002` schema.

Different levels are detected and placed on each tab during mapping action.

After you can map each path from [EAD 2002 paths](https://github.com/biblibre/omeka-s-module-EADImport/blob/master/src/NodeXpaths/EAD2002Xpath.php) hard coded in module. If a path exists on current level you can map it to one or may OmekaS properties.

You can skip a level, either completely and ignore the values of each node, or partially by inheriting one or more values.

Once the import is complete, you can save a mapping so that you can reapply it when importing again.

## How to contribute

You can contribute to this module by adding issues directly [here](https://github.com/biblibre/omeka-s-module-MarcXmlExport/issues).

## Contributors / Sponsors

Contributors:
* [ThibaudGLT](https://github.com/ThibaudGLT)

EADImport was sponsored by:
* [Sciences Po Paris](https://www.sciencespo.fr)

## Licence

MarcXmlExport is distributed under the GNU General Public License, version 3. The full text of this license is given in the LICENSE file.

Created by [BibLibre](https://www.biblibre.com).