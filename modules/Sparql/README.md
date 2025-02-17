Sparql (module for Omeka S)
===========================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Sparql] is a module for [Omeka S] that creates a triplestore and a sparql
server that allows to query json-ld Omeka S database via the [sparql language].
The query can be built via a form in any page or via the endpoint, compliant
with the [sparql protocol version 1.0] and partially version 1.1.

The main interest of a sparql search against api or sql search is that it is a
much more global search: requests are not limited to the database, but to all
the linked data, that can be federated. So this a powerful search tool useful
when you have many relations and normalized data (dates, people, subjects,
locations, etc.), in particular via the module [Value Suggest] and values that
uses common ontologies with right precise usage of each properties and classes.
If you have custom ontologies, publish them and take them stable to allow richer
results.

Furthermore, results may be a list of data, but sparql graphs too.

**WARNING**: This is a work in progress and the [sparql protocol version 1.1] is
not fully implemented yet when using internal sparql endpoint (version 1.0 only).

For a big base or full support of the sparql specifications, in particular the
[sparql protocol version 1.1], it is recommended to use an external sparql
server, like [Fuseki] and to point it to the triplestore created by the module.


Installation
------------

### Module

See general end user documentation for [installing a module].

The module [Common] must be installed first.

The module uses external libraries, so use the release zip to install it, or
use and init the source.

* From the zip

Download the last release [Sparql.zip] from the list of releases (the
master does not contain the dependency), and uncompress it in the `modules`
directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `Sparql`, go to the root of the module, and run:

```sh
composer install --no-dev
```

**Warning**: The module requires php 8.0. If you have only php 7.4, you should
downgrade the version of semsol/arc2 to 2.5 in composer.json.

### Server allowing CORS (Cross-Origin Resource Sharing)

To make the endpoint available from any client, it should be [CORS] compliant.

On Apache 2.4, the module "headers" should be enabled:

```sh
a2enmod headers
systemctl restart apache2
```

Then, you have to add the following rules, adapted to your needs, to the file
`.htaccess` at the root of Omeka S or in the main config of the server:

```apache2
# CORS access for some files.
<IfModule mod_headers.c>
    Header setIfEmpty Access-Control-Allow-Origin "*"
    Header setIfEmpty Access-Control-Allow-Headers "origin, x-requested-with, content-type"
    Header setIfEmpty Access-Control-Allow-Methods "GET, POST"
</IfModule>
```

It is recommended to use the main config of the server, for example  with the
directive `<Directory>`.

To fix Amazon cors issues, see the [aws documentation].


Usage
-----

### Configuration

To be able to query the endpoint, it should be indexed with the internal sparql
endpoint or by an external sparql server.

If you want to use the internal server and an external server, they should have
a different url. The internal server may be available through site pages anyway.

When Fuseki is installed locally, the url to index may be "http://localhost/sparql"
and the external endpoint may be "http://example.org/sparql/triplestore".

### Indexation

Like any other search engines, the module requires to index data in the server.
This is **not** done automatically each time a resource is saved, so the
triplestore should be but updated manually for now in the config form.

You can index the server locally in the database through the integrated library
[semsol/arc2], or you can create a triplestore file, then point to it with an
external sparql server like Fuseki.

### Query

There are two ways to query the search engine.

To query the triplestore according to the standard [sparql protocol version 1.0],
go to https://example.org/sparql. This endpoint is designed for servers.

To query the triplestore with a human interface, create a page with the block
"sparql" and go to it.

If you use an external sparql server, just point it to the triplestore created
by the module.


Apache Jena Fuseki
------------------

An external sparql server is required only for big databases or to support full
sparql protocol version 1.1. One of the most common is [Apache Jena Fuseki].

Fuseki is part of [Jena], a framework incubated by Apache to manage semantic web
and linked data applications. Only the server (Fuseki) and the triple store
database (TDB, integrated in Fuseki) are needed here, since Omeka S and this
module provide the normalized RDF data.

The documentation of [Apache Jena Fuseki] is simple to understand. Here is only
an abstract of the documentation ([quick start] and full documentation for
[production environment]) to run Fuseki on a local machine alongside with Omeka S
and managed by systemd and is enough in most of the cases. Check all other ways
if you need them (standalone service, docker, tomcat, jetty, remotely, etc.).

See below too for the command line (wrapper to jar) that can be used without
configuration for testing purposes.

Logs are managed by syslog by default, or in /etc/fuseki/logs/.

**IMPORTANT**: The admin url of fuseki web app is like "http://example.org/sparql/$/datasets/triplestore/",
but the endpoint to query is like "http://example.org/sparql/triplestore/".
Furthermore, when a proxy is used, it may be internally like "http://localhost/triplestore/".

_Note_: for historical reasons, Jena names rdf graphs "models" and rdf triples
"declarations".

### Quick start for development

1. Download and install Fuseki 2

  Fuseki requires Java 11 (OpenJdk 11), but the last version of java is
  recommended (OpenJdk 17 or newer).

  ```sh
  # Check if java is installed with the good version.
  java -version
  # If not installed, install it (uncomment line below).
  #sudo apt install default-jdk-headless
  # On CentOs:
  #sudo dnf install java-17-openjdk-headless
  # If the certificate is obsolete on Apache server, add --no-check-certificate.
  # To install another version, just change all next version numbers below.
  cd /opt
  sudo wget https://dlcdn.apache.org/jena/binaries/apache-jena-fuseki-4.10.0.tar.gz
  sudo tar -xvf /opt/apache-jena-fuseki-4.10.0.tar.gz
  # Add a symlink to simplify long term management and because /opt/fuseki is used the default in the config.
  sudo ln -s /opt/apache-jena-fuseki-4.10.0 /opt/fuseki
  # Clean the sources if wanted.
  sudo rm apache-jena-fuseki-4.10.0.tar.gz
  ```

2. Information

  Full details of the files for various run modes are available in /opt/apache-jena/fuseki/readme
  so it is recommended to read it. Two main variables are used:

  * FUSEKI_HOME is the dir where fuseki is installed = /opt/fuseki/
  * FUSEKI_BASE is the dir "/run" inside the current directory. When running
    fuseki manually from command line, it is recommended to create a dir like ~/fuseki
    (`mkdir ~/fuseki`) and to go inside it (`cd ~/fuseki`) before running fuseki.
    If you use the default config of the systemd service (see below), the base
    is /etc/fuseki.

3. For quick test on command line

  ```sh
  # Go to a directory where you can run the server without root rights.
  # It can be a home directory, /tmp/fuseki, or /run/fuseki.
  mkdir ~/fuseki
  cd ~/fuseki
  # Set the triple store database according to your real path.
  # Of course, the Omeka S rdf database should have been indexed via the module.
  TSDB="/var/www/html/omekas/files/triplestore/triplestore.ttl"
  # The name of the triple store database, that is used as base path by fuseki.
  # Here, the name is sparql, but it may be /omekas or anything else.
  TSPATHNAME="/sparql"
  /opt/fuseki/fuseki-server --help
  /opt/fuseki/fuseki-server --file="$TSDB" $TSPATHNAME
  ```
  Then, just browse to http://localhost:3030/

### For production environment

  **WARNING**: By default, the database exposed is fully accessible, so it is
  important to protect it. Furthermore, you should not index private resources
  in that case, because there is no authorization checks by default. See below
  for such a security.

  Nevertheless, Fuseki is fully available via localhost (with a password if set),
  so the triplestore can be managed dynamically via the module, while secure for
  external access.

1. Download and install Fuseki 2

  See points 1 and 2 from the quick start above and stop or kill the server
  started in 3.

2. Prepare the configuration of Omeka S as source for Fuseki

  Fuseki uses two config files managed as a rdf graph, generally formatted as
  turtle, but any serialization can be used. They allows to describe the
  server and services (read, querying, update, etc.) over the Omeka S dataset.
  Here, `$FUSEKI_BASE` is `/etc/fuseki` by default:
  - `$FUSEKI_BASE/config.ttl`: server wide configuration
  - `$FUSEKI_BASE/configuration/{tspathname.ttl}`: dataset specific configuration

  TODO Include default config of Fuseki.

  The default omeka s file is included in the module [data/fusuki/config.ttl],
  so you just have to copy it and to change the path to the omeka files/triplestore
  inside it. It allows to publish one dataset as read-only. See the [config documentation]
  for more details.

  ```sh
  # Adapt to your path. The name is renamed too at your choice.
  sudo cp /var/www/html/omekas/modules/Sparql/config/fuseki.ttl /etc/fusuki/configuration/omekas.ttl
  ```

3. Prepare the installation of Fuseki

  ```sh
  sudo touch /etc/default/fuseki
  sudo echo 'FUSEKI_HOME=/opt/fuseki/' | sudo tee -a /etc/default/fuseki
  ```

4. Install fuseki as a service

  You can install the service as a simple old school init.d script (managed by
  systemd anyway) or directly as a systemd service (recommended and more
  secure).

  4.1. Service for systemd

  ```sh
  sudo cp /opt/fuseki/fuseki.service /etc/systemd/system/
  sudo useradd -r -s /bin/false fuseki
  # Take care that the user fuseki should access /etc/fuseki.
  sudo mkdir /etc/fuseki
  sudo chown fuseki:fuseki /etc/fuseki
  # Take care that the user fuseki should access the omeka s triple store.
  # Depending on your web server configuration, you can make readable the
  # Omeka S directory files/triplestore, or add the user to the server group,
  # or store the triplestore somewhere else. A read only access is enough.
  # You should take care of parents too.
  sudo chmod -R o+rX /var/www/html/omekas/files/triplestore/
  ```

  4.2. Service for init.d (deprecated)

  ```sh
  sudo cp /opt/apache-jena-fuseki/fuseki /etc/init.d/
  sudo chmod +x /etc/init.d/fuseki
  ```

5. Enable the service

  ```sh
  # Auto run on boot.
  sudo systemctl enable fuseki
  sudo systemctl start fuseki
  # Check working.
  sudo systemctl status fuseki
  ```

6. Use Apache as a reverse proxy for Fuseki

  To avoid to open the Fuseki service directly to the web and to avoid
  complexity with certificate management, it is recommended to use Apache as a
  reverse proxy.

  To configure a reverse proxy for Fuseki with Apache, you can either redirect
  a path on your domain (https://example.org/sparql) or create a subdomain (https://sparql.example.org).
  In the first case, you edit an existing virtual host file and in the second
  one you create a new one. Here, the example is a path inside the main domain.
  It is recommended to use a generic path like /sparql instead /fuseki,
  because you will not break the url if you change of sparql server in the
  future.

  So edit the file "/etc/apache2/sites-available/example.org.conf":

  ```apache
  <VirtualHost *:443>
      ServerName example.org
      # Other configs.
      …

      # Reverse proxy for Fuseki.
      # In lines below, replace "/sparql" by the path you want.
      ProxyPreserveHost On
      ProxyRequests Off
      <Proxy *>
          Require all granted
      </Proxy>
      RewriteEngine on
      RewriteRule ^/sparql$ /sparql/ [R]
      ProxyPass /sparql/  http://localhost:3030/
      ProxyPassReverse /sparql/  http://localhost:3030/
  </VirtualHost>
  ```

  Here, Fuseki should be available through standard https port 443 without ssl
  on the server. See another similar configuration for [tomcat here].

  For the web app, when the install use a path inside the main domain, you have
  to make the static files in /opt/fuseki/webapp/static available from the
  server too, so copy them or create a link:

  ```sh
  # TODO Find a better way via config.
  sudo ln -s /opt/fuseki/webapp/static /var/www/html/static
  ```

  Then enable this new config:

  ```sh
  # Select apache modules you need according to your config (http and/or fgci).
  sudo a2enmod proxy
  sudo a2enmod proxy_http
  sudo a2enmod proxy_fcgi
  sudo a2ensite mydomain
  sudo systemctl restart apache2
  ```

7. Security

  Fuseki webapp provides security by using [Apache Shiro]. It is integrated in
  Fuseki, so you just have to edit the file `$FUSEKI_BASE/run/shiro.ini`.
  The default username/password is `admin`/`pw`, but it must be changed in the
  section `[users]`. You can create other users and roles if needed, for
  example when there are multiple triple stores for sites.

  In the config shiro.ini file, you can limit access to some urls. The urls
  starting with `/$/` are admin functions.

  For fine data access control, see [Fuseki documentation]. Of course, it is
  useless if only public data are published as read-only.

  For more infos, check the documentation about [Fuseki security].

8. Reload/restart

  After modifying config or security settings, you need to restart the service.

  ```sh
  sudo systemctl restart fuseki
  ```


TODO
----

- [ ] Support of sparql protocol version 1.1.
- [ ] Support of automatic pagination with the omeka paginator.
- [ ] Human interface via https://sparnatural.eu/
- [x] Yasgui interface.
- [ ] Other sparql interfaces than yasgui.
- [ ] Yasgui gallery, charts and timeline plugins (see https://yasgui.triply.cc).
- [ ] Include sparql graph by default.
- [ ] Exploration tools of Nicolas Lasolle, that are adapted to Omeka S.
- [ ] Other visualization and exploration tools (see Nicolas Lasolle [abstract written for a congress]).
- [ ] Query on private resources.
- [ ] Use api credentials for sparql queries.
- [ ] Make a cron task (module [Easy Admin])?
- [ ] Integrate with module [Advanced Search] for indexation.
- [ ] Add button for indexing in module Advanced Search.
- [ ] Triple stores by site or via queries.
- [ ] Manage multiple triplestores.
- [ ] Integrate full text search with lucene (see https://jena.apache.org/documentation/query/text-query.html)
- [ ] Use the external engine with the simple form (require to manage spaql response).
- [x] Readme for [Apache Jena Fuseki].
- [ ] Index directly from omeka json-ld endpoints /api/xxx into Fuseki.
- [ ] Create a Fuseki TDB2 template adapted to Omeka.
- [ ] Include default config for Fuseki adapted to Omeka S.
- [ ] Support create and update of resources through sparql and api.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

### Module

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.

### Libraries

- [semsol/arc2]: either GPL-2.0-or-later or W3C
- [TriplyDB/yasgui]: MIT


Copyright
---------

* Copyright Daniel Berthereau, 2023-2024 (see [Daniel-KM] on GitLab)

* Inspiration

An independant example of rdf visualization from an [Omeka S database] is https://henripoincare.fr
and the work made by [Nicolas Lasolle] for a [thesis in computing] on [Archives Henri Poincaré]
(see [abstract written for a congress]). You can see an example of querying and
results in a [short video].

The python tool [Omeka S to Rdf] is not used because EasyRdf is integrated in
Omeka, so conversion are automatically done.

* Funding

This module was developed for the future digital library [Manioc] of the
Université des Antilles et de la Guyane, currently managed via Greenstone.


[Sparql]: https://gitlab.com/Daniel-KM/Omeka-S-module-Sparql
[Omeka S]: https://omeka.org/s
[Value Suggest]: https://omeka.org/s/modules/ValueSuggest
[Fuseki]: https://jena.apache.org/documentation/fuseki2
[sparql language]: https://www.w3.org/TR/2013/REC-sparql11-query-20130321
[sparql protocol version 1.0]: http://www.w3.org/TR/2008/REC-rdf-sparql-protocol-20080115
[sparql protocol version 1.1]: http://www.w3.org/TR/rdf-sparql-protocol
[installing a module]: https://omeka.org/s/docs/user-manual/modules
[Sparql.zip]: https://github.com/Daniel-KM/Omeka-S-module-Sparql/releases
[CORS]: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
[aws documentation]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/cors.html
[Apache Jena Fuseki]: https://jena.apache.org/documentation/fuseki2
[Jena]: https://jena.apache.org/
[quick start]: https://jena.apache.org/documentation/fuseki2/fuseki-quick-start.html
[production environment]: https://jena.apache.org/documentation/fuseki2/fuseki-webapp.html
[config documentation]: https://jena.apache.org/documentation/fuseki2/fuseki-configuration.html#fuseki-configuration-file
[tomcat here]: https://nvbach.blogspot.com/2018/07/apache-jena-fuseki-on-debian-9-from.html
[Apache Shiro]: https://jena.apache.org/documentation/fuseki2/fuseki-security
[Fuseki documentation]: https://jena.apache.org/documentation/fuseki2/fuseki-data-access-control.html
[Fuseki security]: https://jena.apache.org/documentation/fuseki2/fuseki-security.html
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Sparql/issues
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[Easy Admin]: https://gitlab.com/Daniel-KM/Omeka-S-module-EasyAdmin
[Advanced Search]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedSearch
[Omeka S database]: http://henripoincare.fr
[Nicolas Lasolle]: https://github.com/nlasolle
[Thesis in computing]: https://hal.univ-lorraine.fr/tel-03845484
[abstract written for a congress]: https://inserm.hal.science/LORIA-NLPKD/hal-03406713v1
[Archives Henri Poincaré]: https://www.ahp-numerique.fr
[short video]: https://videos.ahp-numerique.fr/w/gjj2DJ9mZmVNKehwuDgWFk
[Omeka S to Rdf]: https://github.com/nlasolle/omekas2rdf
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://opensource.org/licenses/MIT
[semsol/arc2]: https://github.com/semsol/arc2
[TriplyDB/yasgui]: https://github.com/TriplyDB/Yasgui
[Manioc]: https://manioc.org
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
