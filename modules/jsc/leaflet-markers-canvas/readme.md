# leaflet-markers-canvas

A Leaflet plugin to render many markers in a canvas instead of the DOM.

This is a complete rewrite of [Leaflet.Canvas-Markers](https://github.com/eJuke/Leaflet.Canvas-Markers) by Eugene Voynov. Thank you for the inspiration.

## Demo

Here is a [demo](https://francoisromain.github.io/leaflet-markers-canvas/examples/) of 10000 markers, displayed in one canvas.

## Usage

### Dependencies

[Leaflet](https://leafletjs.com/) and [RBush](https://github.com/mourner/rbush) must be available globally or installed as peer-dependencies.

### Install

- Install from npm: `npm i leaflet-markers-canvas`
- or download [leaflet-markers-canvas.js](https://github.com/francoisromain/leaflet-markers-canvas/blob/master/dist/leaflet-markers-canvas.js)

### Exemple

```js
var map = L.map("map").setView([59.9578, 30.2987], 10);
var tiles = L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
  attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>',
  preferCanvas: true,
}).addTo(map);

var markersCanvas = new L.MarkersCanvas();
markersCanvas.addTo(map);

var icon = L.icon({
  iconUrl: "marker.png",
  iconSize: [20, 32],
  iconAnchor: [10, 0],
});

var markers = [];

for (var i = 0; i < 10000; i++) {
  var marker = L.marker(
    [58.5578 + Math.random() * 1.8, 29.0087 + Math.random() * 3.6],
    { icon }
  )
    .bindPopup("I Am " + i)
    .on({
      mouseover(e) {
        this.openPopup();
      },
      mouseout(e) {
        this.closePopup();
      },
    });

  markers.push(marker);
}

markersCanvas.addMarkers(markers);
```

## Methods

### `addTo(map)`

### `getBounds()`

### `redraw()`

### `clear()`

### `addMarker(marker)`

### `addMarkers(markers)`

### `removeMarker(marker)`

### `removeMarkers(markers)`

## To-do

- Complete documentation
