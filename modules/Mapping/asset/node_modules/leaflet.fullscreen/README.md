Leaflet.Control.FullScreen
============

What ?
------

Simple plugin for Leaflet that adds fullscreen button to your maps.

Inspired by http://elidupuis.github.com/leaflet.zoomfs/

Use the native javascript fullscreen API with help of https://github.com/sindresorhus/screenfull.js

Icons from [Font Awesome v5.15.4](https://github.com/FortAwesome/Font-Awesome/releases/tag/5.15.4): [Creative Commons Attribution 4.0](https://fontawesome.com/license/free)

Released under the MIT License http://opensource.org/licenses/mit-license.php

How ?
------

Include Control.FullScreen.js and Control.FullScreen.css in your page:

``` html
 <link rel="stylesheet" href="Control.FullScreen.css" />
 <script src="Control.FullScreen.js"></script>
```

Add the fullscreen control to the map:

``` js
var map = new L.Map('map', {
  fullscreenControl: true,
  fullscreenControlOptions: {
    position: 'topleft'
  }
});
```

If your map has a zoomControl the fullscreen button will be added at the bottom of this one.

If your map doesn't have a zoomControl the fullscreen button will be added to topleft corner of the map (same as the zoomControl).

If you want to use the plugin on a map embedded in an iframe, don't forget to set `allowfullscreen` attribute on your iframe.

__Events and options__:

``` js
// create a fullscreen button and add it to the map
L.control.fullscreen({
  position: 'topleft', // change the position of the button can be topleft, topright, bottomright or bottomleft, default topleft
  title: 'Show me the fullscreen !', // change the title of the button, default Full Screen
  titleCancel: 'Exit fullscreen mode', // change the title of the button when fullscreen is on, default Exit Full Screen
  content: null, // change the content of the button, can be HTML, default null
  forceSeparateButton: true, // force separate button to detach from zoom buttons, default false
  forcePseudoFullscreen: true, // force use of pseudo full screen even if full screen API is available, default false
  fullscreenElement: false // Dom element to render in full screen, false by default, fallback to map._container
}).addTo(map);

// events are fired when entering or exiting fullscreen.
map.on('enterFullscreen', function(){
  console.log('entered fullscreen');
});

map.on('exitFullscreen', function(){
  console.log('exited fullscreen');
});

// you can also toggle fullscreen from map object
map.toggleFullscreen();
```

Where ?
------

Source code : https://github.com/brunob/leaflet.fullscreen

Downloads : https://github.com/brunob/leaflet.fullscreen/releases

Demo : https://brunob.github.io/leaflet.fullscreen/
