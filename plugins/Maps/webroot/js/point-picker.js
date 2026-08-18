/**
 * Picking a single point off the map.
 *
 * Clicking the map writes the coordinates into the form fields and moves the marker. The address
 * search asks the application rather than a geocoder directly, so which geocoder answers - and the
 * key it is reached with - stays on the server.
 */
(function (window, document) {
    'use strict';

    var DEBOUNCE_MS = 300;
    var MIN_QUERY_LENGTH = 3;
    var RESULT_LIMIT = 5;

    /**
     * Removes the results dropdown.
     */
    function clearResults(list) {
        list.innerHTML = '';
        list.hidden = true;
    }

    function initPicker(element) {
        var described = JSON.parse(element.getAttribute('data-maps-point-picker'));
        var built = window.MapsPlugin.ensure(element);
        var map = built.map;
        var marker = built.markers[0];

        var latInput = document.getElementById(described.latInputId);
        var lngInput = document.getElementById(described.lngInputId);
        var searchInput = document.getElementById(described.searchInputId);

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

        if (!searchInput || !described.searchUrl) {
            return;
        }

        var list = document.createElement('ul');
        list.className = 'maps-search-results';
        list.hidden = true;
        // The dropdown is absolutely positioned against the field wrapper.
        searchInput.parentNode.style.position = 'relative';
        searchInput.parentNode.appendChild(list);

        var timer = null;

        function search(query) {
            var url = described.searchUrl
                + (described.searchUrl.indexOf('?') === -1 ? '?' : '&')
                + 'q=' + encodeURIComponent(query)
                + '&limit=' + RESULT_LIMIT
                + (described.country ? '&country=' + encodeURIComponent(described.country) : '');

            fetch(url, { headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Address search responded with ' + response.status);
                    }

                    return response.json();
                })
                .then(function (data) {
                    render(data.suggestions || []);
                })
                .catch(function (error) {
                    // A failing address search must not break the form, the map
                    // stays usable for picking the point by hand.
                    clearResults(list);
                    if (window.console) {
                        window.console.warn('Address search failed:', error);
                    }
                });
        }

        function render(suggestions) {
            list.innerHTML = '';

            if (!suggestions.length) {
                list.hidden = true;

                return;
            }

            suggestions.forEach(function (suggestion) {
                var item = document.createElement('li');
                item.textContent = suggestion.label;
                item.addEventListener('click', function () {
                    setPoint(suggestion.lat, suggestion.lng);
                    map.setView([suggestion.lat, suggestion.lng], Math.max(map.getZoom(), 16));
                    searchInput.value = suggestion.label;
                    clearResults(list);
                });
                list.appendChild(item);
            });

            list.hidden = false;
        }

        searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim();

            window.clearTimeout(timer);

            if (query.length < MIN_QUERY_LENGTH) {
                clearResults(list);

                return;
            }

            timer = window.setTimeout(function () {
                search(query);
            }, DEBOUNCE_MS);
        });

        // The search field lives inside a form, Enter would submit it.
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target !== searchInput && !list.contains(event.target)) {
                clearResults(list);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-maps-point-picker]'),
            initPicker
        );
    });
})(window, document);
