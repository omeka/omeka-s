<?php

namespace EADImport\NodeXpaths;

class EAD2002Xpath
{
    public function getPaths()
    {
        $paths = [
        '@id',

        './did/unitid',
        './did/unittitle',
        './did/unittitle/title',
        './did/unittitle/date',
        './did/unittitle/unitdate',
        './did/unittitle/genreform',
        './did/unittitle/corpname',
        './did/unittitle/famname',
        './did/unittitle/function',
        './did/unittitle/genreform',
        './did/unittitle/geogname',
        './did/unittitle/name',
        './did/unittitle/occupation',
        './did/unittitle/persname',
        './did/unittitle/subject',

        './did/unitdate',

        './did/physdesc',

        './did/physdesc/corpname',
        './did/physdesc/date',
        './did/physdesc/dimensions',
        './did/physdesc/famname',
        './did/physdesc/geogname',
        './did/physdesc/name',
        './did/physdesc/persname',
        './did/physdesc/subject',
        './did/physdesc/title',
        './did/physdesc/title/date',
        './did/physdesc/extent',
        './did/physdesc/physfacet',
        './did/physdesc/physfacet/corpname',
        './did/physdesc/physfacet/famname',
        './did/physdesc/physfacet/geogname',
        './did/physdesc/physfacet/name',
        './did/physdesc/physfacet/persname',
        './did/physdesc/physfacet/date',
        './did/physdesc/physfacet/subject',
        './did/physdesc/physfacet/title',
        './did/physdesc/physfacet/title/date',
        './did/physdesc/physfacet/genreform',

        './did/langmaterial',
        './did/langmaterial/language',

        './did/origination',
        './did/origination/persname',
        './did/origination/famname',
        './did/origination/corpname',
        './did/origination/name',

        './did/repository/corpname',
        './did/repository/address/addressline',

        './did/physloc',

        './did/materialspec',

        './accessrestrict',
        './accessrestrict/legalstatus',
        './accessrestrict/p',

        './accruals/p',

        './acqinfo/p',

        './altformavail/p',

        './appraisal/p',

        './arrangement/p',

        './bibliography/bibref',
        './bibliography/bibliography',
        './bibliography/extref',
        './bibliography/head',
        './bibliography/p',
        './bibliography/bibliography/bibref',
        './bibliography/bibliography/extref',
        './bibliography/bibliography/head',
        './bibliography/bibliography/p',

        './bioghist/p',
        './bioghist/head',
        './bioghist/dao/daodesc/p',
        './bioghist/daogrp/daoloc/daodesc/p',
        './bioghist/daogrp/daodesc/p',

        './custodhist/p',

        './dao/daodesc/p',

        './daogrp/daoloc/daodesc/p',
        './daogrp/daodesc/p',

        './fileplan/p',

        './note/p',

        './originalsloc/p',

        './otherfindaid/archref',
        './otherfindaid/bibref',
        './otherfindaid/p',

        './phystech/p',

        './prefercite/p',

        './processinfo/p',
        './processinfo/head',

        './relatedmaterial/p',
        './relatedmaterial/head',

        './scopecontent/p',
        './scopecontent/dao/daodesc/p',
        './scopecontent/daogrp/daoloc/daodesc/p',
        './scopecontent/daogrp/daodesc/p',

        './separatedmaterial/p',

        './userestrict/p',

        './odd/p',

        './controlaccess/blockquote/p',
        './controlaccess/corpname',
        './controlaccess/famname',
        './controlaccess/function',
        './controlaccess/genreform',
        './controlaccess/geogname',
        './controlaccess/head',
        './controlaccess/name',
        './controlaccess/occupation',
        './controlaccess/persname',
        './controlaccess/subject',
        './controlaccess/title',
        './controlaccess/title/date',

    ];

        return $paths;
    }
}
