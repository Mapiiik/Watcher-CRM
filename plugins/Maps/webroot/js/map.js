/**
 * Builds the maps the server described.
 *
 * A map is a div carrying its whole description in `data-maps`: where to look, what to draw
 * it on, and what to draw. Nothing about it is written as script by PHP, so the same description
 * serves a read only overview and the editable point picker alike.
 */
(function (window, document) {
    'use strict';

    /**
     * Leaflet has no coloured pin of its own, so one is drawn here.
     */
    function markerIcon(L, color) {
        return L.divIcon({
            className: 'maps-pin',
            html: '<svg width="24" height="30" viewBox="0 0 24 30" xmlns="http://www.w3.org/2000/svg">'
                + '<path d="M12 0C7.16 0 3 4.56 3 10.08c0 6.48 9 19.92 9 19.92s9-13.44 9-19.92'
                + 'C21 4.56 16.84 0 12 0zm0 14.4c-1.8 0-3.24-1.44-3.24-3.24s1.44-3.24 3.24-3.24'
                + ' 3.24 1.44 3.24 3.24-1.44 3.24-3.24 3.24z" '
                + 'fill="' + (color || '#3388ff') + '" stroke="rgba(0,0,0,0.5)" stroke-width="0.5"/></svg>',
            iconSize: [24, 30],
            iconAnchor: [12, 30],
            popupAnchor: [0, -30]
        });
    }

    /**
     * WMS is here because aerial imagery is often published no other way.
     */
    function buildLayer(L, layer) {
        var options = layer.options || {};

        if (layer.type === 'wms') {
            return L.tileLayer.wms(layer.url, options);
        }

        return L.tileLayer(layer.url, options);
    }

    function addBaseLayers(L, map, layers) {
        if (!layers || !layers.length) {
            return;
        }

        var baseMaps = {};

        layers.forEach(function (layer, index) {
            var tileLayer = buildLayer(L, layer);
            baseMaps[layer.name] = tileLayer;

            // The first configured layer is the one shown on load.
            if (index === 0) {
                tileLayer.addTo(map);
            }
        });

        if (layers.length > 1) {
            L.control.layers(baseMaps).addTo(map);
        }
    }

    function addMarkers(L, map, described) {
        return (described || []).map(function (one) {
            var marker = L.marker([one.lat, one.lng], {
                icon: markerIcon(L, one.color),
                title: one.title || '',
                draggable: Boolean(one.draggable)
            }).addTo(map);

            if (one.content) {
                // Leaflet sizes a popup to its content, which turns a short bubble into a tall
                // sliver and gives a long one only 300px. Both ends are held instead, the upper
                // one wide enough that a row of dashes written as a separator stays one row -
                // a browser may break a line after any of them. A tall bubble is given a scroll
                // bar rather than being allowed to run off the map.
                marker.bindPopup(one.content, { minWidth: 240, maxWidth: 480, maxHeight: 360 });
            }

            return marker;
        });
    }

    function addPolylines(L, map, described) {
        (described || []).forEach(function (line) {
            L.polyline([[line.from.lat, line.from.lng], [line.to.lat, line.to.lng]], line.options || {}).addTo(map);
        });
    }

    /**
     * Fits the map to what is on it, or looks where it was told to.
     */
    function look(L, map, view, markers) {
        if (view && view.fit && markers.length) {
            map.fitBounds(L.featureGroup(markers).getBounds().pad(0.1));

            return;
        }

        map.setView([view.lat, view.lng], view.zoom);
    }

    function build(element) {
        var L = window.L;
        var described = JSON.parse(element.getAttribute('data-maps'));
        var map = L.map(element, described.map || {});

        addBaseLayers(L, map, described.baseLayers);
        addPolylines(L, map, described.polylines);

        var markers = addMarkers(L, map, described.markers);

        look(L, map, described.view || {}, markers);

        if (described.fullscreen && typeof window.MapsPlugin.fullscreen === 'function') {
            window.MapsPlugin.fullscreen(map, described.fullscreen);
        }

        element.mapsInstance = { map: map, markers: markers };

        return element.mapsInstance;
    }

    /**
     * The built map, building it first if whoever asks got here before the page was ready.
     */
    function ensure(element) {
        return element.mapsInstance || build(element);
    }

    function initAll() {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-maps]'),
            ensure
        );
    }

    window.MapsPlugin = window.MapsPlugin || {};
    window.MapsPlugin.build = build;
    window.MapsPlugin.ensure = ensure;
    window.MapsPlugin.initAll = initAll;

    document.addEventListener('DOMContentLoaded', initAll);
})(window, document);
