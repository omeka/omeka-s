'use strict';

$(document).ready(function() {

    const yasgui = new Yasgui(document.getElementById('yasgui'), {
        requestConfig: {
            endpoint: sparqlEndpoint,
        },
        copyEndpointOnNewTab: false,
        yasqe: {
            value: sparqlQuery,
        },
    });

});
