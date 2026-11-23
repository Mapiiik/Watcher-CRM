/**
 * The address search on an overview map.
 *
 * The address picked gets a mark of its own, and the map shows it together with the nearest of
 * what it draws. Its bubble lists those with how far each one is.
 */
(function (window, document) {
    'use strict';

    // How many of the nearest the view is fitted to, and how many the bubble lists.
    var FITTED = 3;
    var LISTED = 5;
    var CLOSEST_ZOOM = 16;

    /**
     * A ring rather than a pin, so it is not taken for a marker of any colour.
     */
    function addressIcon(L) {
        return L.divIcon({
            className: 'maps-search-pin',
            html: '<svg width="22" height="22" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">'
                + '<circle cx="11" cy="11" r="8" fill="#fff" stroke="#000" stroke-width="3"/>'
                + '<circle cx="11" cy="11" r="3" fill="#000"/></svg>',
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -11]
        });
    }

    function distance(meters, locale) {
        var far = meters >= 1000;

        return new Intl.NumberFormat(locale || undefined, {
            style: 'unit',
            unit: far ? 'kilometer' : 'meter',
            maximumFractionDigits: far ? 1 : 0
        }).format(far ? meters / 1000 : meters);
    }

    /**
     * What the map draws with a name, nearest first.
     */
    function nearest(map, markers, latlng) {
        return markers
            .filter(function (marker) {
                return Boolean(marker.options.title);
            })
            .map(function (marker) {
                return { marker: marker, meters: map.distance(latlng, marker.getLatLng()) };
            })
            .sort(function (one, other) {
                return one.meters - other.meters;
            });
    }

    /**
     * The bubble of the address. Built of nodes, never of markup, because the address is the
     * geocoder's text.
     */
    function bubble(map, label, ranked, described) {
        var root = document.createElement('div');
        var heading = document.createElement('div');

        root.className = 'maps-bubble';
        heading.className = 'maps-bubble-heading';
        heading.textContent = label;
        root.appendChild(heading);

        if (!ranked.length) {
            return root;
        }

        var caption = document.createElement('p');
        var list = document.createElement('ul');

        caption.className = 'maps-bubble-meta';
        caption.textContent = (described.labels || {}).nearest || '';
        list.className = 'maps-nearest';

        ranked.slice(0, LISTED).forEach(function (one) {
            var item = document.createElement('li');
            var link = document.createElement('a');

            link.href = '#';
            link.textContent = one.marker.options.title;
            link.addEventListener('click', function (event) {
                event.preventDefault();

                if (one.marker.getPopup()) {
                    one.marker.openPopup();
                } else {
                    map.panTo(one.marker.getLatLng());
                }
            });

            item.appendChild(link);
            item.appendChild(document.createTextNode(' – ' + distance(one.meters, described.locale)));
            list.appendChild(item);
        });

        root.appendChild(caption);
        root.appendChild(list);

        return root;
    }

    function initSearch(element) {
        var L = window.L;
        var described = JSON.parse(element.getAttribute('data-maps-search'));
        var built = window.MapsPlugin.ensure(element);
        var map = built.map;
        var mark = null;

        function show(suggestion) {
            var latlng = L.latLng(suggestion.lat, suggestion.lng);
            var ranked = nearest(map, built.markers, latlng);

            if (mark) {
                map.removeLayer(mark);
            }

            mark = L.marker(latlng, {
                icon: addressIcon(L),
                title: suggestion.label,
                zIndexOffset: 1000
            }).addTo(map);
            mark.bindPopup(bubble(map, suggestion.label, ranked, described), {
                minWidth: 240,
                maxWidth: 480,
                maxHeight: 360
            });

            // The bubble opens once the map has got there, so it pans the view it ends in. The
            // end may come before the call returns, hence listening first.
            var opened = mark;
            map.once('moveend', function () {
                if (opened === mark) {
                    opened.openPopup();
                }
            });

            if (!ranked.length) {
                map.setView(latlng, CLOSEST_ZOOM);

                return;
            }

            var bounds = L.latLngBounds([latlng]);
            ranked.slice(0, FITTED).forEach(function (one) {
                bounds.extend(one.marker.getLatLng());
            });
            map.fitBounds(bounds.pad(0.1), { maxZoom: CLOSEST_ZOOM });
        }

        window.MapsPlugin.addressSearch(
            map,
            {
                url: described.searchUrl,
                country: described.country,
                placeholder: (described.labels || {}).search
            },
            show
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-maps-search]'),
            initSearch
        );
    });
})(window, document);
