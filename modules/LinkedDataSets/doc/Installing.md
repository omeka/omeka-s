# Installing the Linked Data Sets module

Download the [latest release](https://github.com/netwerk-digitaal-erfgoed/Omeka-S-Module-LinkedDataSets/releases) from this repository and extract it in the modules directory of your Omeka S installation. Then install the Linked Data Sets module as admin from the Modules page.

If the required modules are present and the Omeka S version is 4.0.0 or higher, the installation adds the following:
- the schema.org vocabulaire (prefix `sdo`)
- custom vocabs LDS licenses, LDS IETF Language Tags and LDS Media Types.
- the resource templates LDS Datacatalog, LDS Dataset, LDS Distribution, LDS Person and LDS Organization (based on the [conceptual model](ConceptualModel.md))
- two jobs to make a data catalog in RDF format (to be placed in the files/datacatalogs directory) and to make a datadump of an itemset (to be places in the files/datadumps directory)