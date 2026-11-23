/**
 * Picking a single point off the map.
 *
 * Clicking the map writes the coordinates into the form fields and moves the marker. So does
 * picking an address from the search inside the map.
 */
(function (window, document) {
    'use strict';

    function initPicker(element) {
        var described = JSON.parse(element.getAttribute('data-maps-point-picker'));
        var built = window.MapsPlugin.ensure(element);
        var map = built.map;
        var marker = built.markers[0];

        var latInput = document.getElementById(described.latInputId);
        var lngInput = document.getElementById(described.lngInputId);

        /**
         * Moves the marker and writes the coordinates into the form.
         */
        function setPoint(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            }
            if (latInput) {
                latInput.value = lat;
            }
            if (lngInput) {
                lngInput.value = lng;
            }
        }

        map.on('click', function (event) {
            setPoint(event.latlng.lat, event.latlng.lng);
        });

        if (!described.searchUrl) {
            return;
        }

        window.MapsPlugin.addressSearch(
            map,
            {
                url: described.searchUrl,
                country: described.country,
                placeholder: (described.labels || {}).search
            },
            function (suggestion) {
                setPoint(suggestion.lat, suggestion.lng);
                map.setView([suggestion.lat, suggestion.lng], Math.max(map.getZoom(), 16));
            }
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-maps-point-picker]'),
            initPicker
        );
    });
})(window, document);
