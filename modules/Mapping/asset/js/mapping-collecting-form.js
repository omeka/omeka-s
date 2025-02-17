$(document).ready(function() {
    $('.collecting-map').each(function() {

        var mapDiv = $(this);
        var inputLat = mapDiv.siblings('input.collecting-map-lat');
        var inputLng = mapDiv.siblings('input.collecting-map-lng');

        mapDiv.css('cursor', 'crosshair');

        var map = L.map(this, {
            fullscreenControl: true,
            worldCopyJump:true
        });
        var marker;

        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        map.setView([20, 0], 2);

        map.addControl(new window.GeoSearch.GeoSearchControl({
            provider: new window.GeoSearch.OpenStreetMapProvider,
            showMarker: false,
        }));

        // Add the marker to the map.
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = new L.marker(e.latlng).addTo(map);
            // Account for markers placed outside the CRS's bounds.
            var latLng = marker.getLatLng().wrap();
            inputLat.val(latLng.lat);
            inputLng.val(latLng.lng);
        });

        // Remove the marker if it's clicked.
        map.on('layeradd', function(e) {
            if (e.layer instanceof L.Marker) {
                $(e.layer).on('click', function(e) {
                    map.removeLayer(marker);
                    inputLat.val('');
                    inputLng.val('');
                });
            }
        });

        // Prevent click-throughs (otherwise clicks will add markers).
        $('.geosearch').on('click', function(e) {
            e.stopPropagation();
        });

        // Add an existing marker to the map.
        var lat = inputLat.val();
        var lng = inputLng.val();
        if (lat && lng) {
            marker = new L.marker(L.latLng(lat, lng)).addTo(map);
        }
    });
});
