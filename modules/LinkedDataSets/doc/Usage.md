# Using the Linked Data Sets module

## 1 - Creating a data catalog with dataset descriptions with distributions

As outlined in the [conceptual model](ConceptualModel.md) a data catalog consists of a set of dataset descriptions, each having one or more distributions. To help in the creation of the associated metadata, the Linked Data Sets (LDS) module [installs](Installing.md) several Resource templates to help.

1. **Define the organization(s) and/or person(s)** which are to be referenced as publisher, creator or funder by creating a new Item based on the LDS Organization / LDS Person templates. 
    - At least provide a name. 
    - It is recommended to also provide information for the rest of the input fields.
    - Save the Item.

2. **Define your data catalog** by creating a new Item based on the LDS DataCatalog template.
    - At least provide a `sdo:name` and attach an `sdo:publisher` item.
    - It is recommended to also provide information for the rest of the input fields. 
    - The `sdo:dataset` field can be skipped for now (as the LDS Dataset Item is yet to be defined in the next step).
    - Save the Item.
    - You can have more than one data catalog.

3. **Define you dataset description** by creating a new Item based on the LDS Dataset. 
    - At least provide a `sdo:name`, attach an `sdo:publisher` item, select an URI of the appropiate `sdo:license`. 
    - It is recommended to also provide information for the rest of the input fields. 
    - The `sdo:distribution` field can be skipped for now (as this LDS Distribtion Item is yet to be defined in the next step).
    - If you want to this dataset to be a RDF datadump of one or more Item Sets, see section 2.1.
    - Save the Item.
    - You can have more than one data set.

4. **Define you a distribution** of a dataset by creating a new Item based on the LDS Distribution. 
    - At least select a `sdo:encodingFormat` from the list and provide and URI for the `sdo:contentUrl`. 
    - It is recommended to also provide information for the rest of the input fields. 
    - If you want to this distribution to be a RDF datadump of one ore more Item Sets, input `TODO` as the `sdo:contentUrl` URI value, see section 2.2. 
    - Save the Item.
    - Repeat this step if a particular dataset needs more distributions which can be an OAI-PMH-endpoint, a SPARQL-endpoint, a SRU API or datadump.

5. **Attach the distribution(s) to the dataset** by editing the LDS Dataset Item from step 3 and selecting the LDS Distribution Item(s) from step 4 as `sdo:distribution`, then save the Item.
    - Repeat this step for all the datasets you defined.

6. **Attach the dataset(s) to the datacatalog** by editing the LDS Datacatalog Item from step 2 and selecting the LDS Dataset Item(s) from step 3 as `sdo:dataset`, then save the Item.

Each time a LDS DataCatalog, LDS Dataset or LDS Distribution item is added or edited, a datacatalog file is created which includes the dataset and distribution descriptions. The datacatalog file is made in four formats (Turtle, N-Triples, JSON-LD and RDF/XML) and is available online immediatly. The exact URL of these files can be found in the log of the `LinkedDataSets\Application\Job\RecreateDataCatalogsJob` job. The structure of the URL of these files is `https://{hostname}/{path of omeka}/files/datacatalogs/datacatalog-{datacatalog item ID}.{ttl|nt|jsonld|xml}`.

You can register your data catalog with an online dataset register (like [https://datasetregister.netwerkdigitaalerfgoed.nl/](https://datasetregister.netwerkdigitaalerfgoed.nl/?lang=en)) by registering one of these URLs.

## 2 - Creating RDF datadumps of Item sets

If you want one of your datasets to have one (or more) distributions to be a RDF datadump of one (or more) defined Item Sets with you Omeka S installation, perform the following steps:

1. **Edit the LDS Distribution Item** and make sure the `sdo:encodingFormat` field is one of the following formats:
    - application/ld+json
    - application/ld+json+gzip
    - application/n-triples
    - application/n-triples+gzip
    - application/rdf+xml
    - application/rdf+xml+gzip
    - text/turtle
    - text/turtle+gzip

2. **Edit the LDS Dataset Item** associated with the LDS Distribution Item from step 1 and select the Item Sets of which the metadata should be in the datadump in the `sdo:isBasedOn` field, then save the Item.

Each time a LDS Dataset Item with an `sdo:isBasedOn` with Item Sets (and linked LDS Distribution Item(s) with a RDF format) is stored, a datajump job will be started. This `LinkedDataSets\Application\Job\DataDumpJob` background job harvests the metadata of all items in the specified Item Sets and converts this to the format as defined in the distribution. Upon completion of the job, the URL of the datadump is put in the `sdo:contenUrl` field. The `sdo:dateModified` and `sdo:contentSize` are also created or updated. Finally, the datacatalog files are updated to reflect the change in distribution metadata.

**Note**: it is not recommended to define multiple LDS Distributions in various RDF formats for one LDS Dataset. Just choose one, if a users need another format than provided this file can be converted easily with available RDF tooling.