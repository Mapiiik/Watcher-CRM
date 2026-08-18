/**
 * Fullscreen control for Leaflet maps.
 *
 * The Google Maps JavaScript API ships a fullscreen button by default, Leaflet
 * does not. This adds an equivalent one built on the native Fullscreen API, so
 * no plugin is needed.
 *
 * Called from the map view elements once the map exists.
 */
(function (window, document) {
    'use strict';

    var ICON = '<svg viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">'
        + '<path fill="currentColor" d="M2 2h5v2H4v3H2V2zm9 0h5v5h-2V4h-3V2zM2 11h2v3h3v2H2v-5zm12 0h2v5h-5v-2h3v-3z"/>'
        + '</svg>';

    /**
     * Browsers still disagree on the Fullscreen API prefix, Safari being the
     * holdout.
     */
    function fullscreenElement() {
        return document.fullscreenElement || document.webkitFullscreenElement || null;
    }

    function requestFullscreen(element) {
        if (element.requestFullscreen) {
            return element.requestFullscreen();
        }
        if (element.webkitRequestFullscreen) {
            return element.webkitRequestFullscreen();
        }

        return null;
    }

    function exitFullscreen() {
        if (document.exitFullscreen) {
            return document.exitFullscreen();
        }
        if (document.webkitExitFullscreen) {
            return document.webkitExitFullscreen();
        }

        return null;
    }

    function isSupported(element) {
        return Boolean(element.requestFullscreen || element.webkitRequestFullscreen);
    }

    function initFullscreen(map, labels) {
        var L = window.L;
        var container = map.getContainer();

        // Nothing to offer when the browser cannot do it, for instance inside an
        // iframe without the allowfullscreen attribute.
        if (!isSupported(container)) {
            return;
        }

        labels = labels || {};

        var control = L.control({ position: 'topleft' });

        control.onAdd = function () {
            var bar = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            var button = L.DomUtil.create('a', 'leaflet-control-fullscreen-button', bar);

            button.href = '#';
            button.innerHTML = ICON;
            button.title = labels.enter || 'Fullscreen';
            button.setAttribute('role', 'button');

            L.DomEvent.disableClickPropagation(bar);
            L.DomEvent.on(button, 'click', function (event) {
                L.DomEvent.preventDefault(event);

                if (fullscreenElement() === container) {
                    exitFullscreen();
                } else {
                    requestFullscreen(container);
                }
            });

            return bar;
        };

        control.addTo(map);

        function onChange() {
            var active = fullscreenElement() === container;
            var button = container.querySelector('.leaflet-control-fullscreen-button');

            if (button) {
                button.title = active
                    ? (labels.exit || 'Exit Fullscreen')
                    : (labels.enter || 'Fullscreen');
            }

            L.DomUtil[active ? 'addClass' : 'removeClass'](container, 'maps-fullscreen');

            // The container just changed size behind Leaflet's back.
            map.invalidateSize();
        }

        document.addEventListener('fullscreenchange', onChange);
        document.addEventListener('webkitfullscreenchange', onChange);
    }

    window.MapsPlugin = window.MapsPlugin || {};
    window.MapsPlugin.fullscreen = initFullscreen;
})(window, document);
