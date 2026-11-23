/**
 * The address search, a field inside the map.
 *
 * It sits beside the zoom buttons, so it stays at hand in fullscreen too. The field asks the
 * application rather than a geocoder directly, so which geocoder answers - and the key it is
 * reached with - stays on the server. What becomes of the address picked is up to whoever put the
 * field on the map.
 */
(function (window, document) {
    'use strict';

    var DEBOUNCE_MS = 300;
    var MIN_QUERY_LENGTH = 3;
    var RESULT_LIMIT = 5;

    // The field gives way to the zoom buttons on its left and the layer switcher on the right.
    var ROOM_BESIDE_THE_FIELD = 130;
    var WIDEST = 480;
    var NARROWEST = 120;

    /**
     * Puts the control right after the zoom buttons. Leaflet stacks the controls of a corner one
     * under another, and this one is let to stand beside them instead.
     */
    function besideTheZoom(map, container) {
        var corner = container.parentNode;
        var zoom = map.zoomControl ? map.zoomControl.getContainer() : null;

        if (zoom && zoom.parentNode === corner) {
            corner.insertBefore(container, zoom.nextSibling);
        } else {
            corner.insertBefore(container, corner.firstChild);
        }
    }

    /**
     * Adds the field to the map and calls onPick with the suggestion picked.
     *
     * @param {L.Map} map
     * @param {{url: string, country: ?string, placeholder: ?string}} options
     * @param {function({label: string, lat: number, lng: number})} onPick
     */
    function addressSearch(map, options, onPick) {
        var L = window.L;
        var control = L.control({ position: 'topleft' });
        var input = null;
        var list = null;
        var timer = null;
        var pending = null;
        var shown = [];
        var shownFor = null;
        var highlighted = -1;

        control.onAdd = function () {
            var bar = L.DomUtil.create('div', 'leaflet-bar leaflet-control maps-search');

            input = L.DomUtil.create('input', '', bar);
            input.type = 'search';
            input.autocomplete = 'off';
            input.placeholder = options.placeholder || '';
            input.setAttribute('aria-label', options.placeholder || '');

            list = L.DomUtil.create('ul', 'maps-search-results', bar);
            list.hidden = true;
            list.setAttribute('role', 'listbox');

            // Neither a click into the field nor a wheel over the results is the map's. A picker
            // would move its point to where the field is, and the map would zoom.
            L.DomEvent.disableClickPropagation(bar);
            L.DomEvent.disableScrollPropagation(bar);
            // A map with the focus pans on the arrows and zooms on + and -.
            L.DomEvent.on(bar, 'keydown keypress keyup', L.DomEvent.stopPropagation);

            return bar;
        };

        control.addTo(map);
        besideTheZoom(map, control.getContainer());

        function fitTheWidth() {
            var room = map.getSize().x - ROOM_BESIDE_THE_FIELD;

            input.style.width = Math.max(NARROWEST, Math.min(WIDEST, room)) + 'px';
        }

        fitTheWidth();
        map.on('resize', fitTheWidth);

        function clearResults() {
            list.innerHTML = '';
            list.hidden = true;
            shown = [];
            shownFor = null;
            highlighted = -1;
        }

        function highlight(index) {
            highlighted = index;
            Array.prototype.forEach.call(list.children, function (item, at) {
                item.setAttribute('aria-selected', at === index ? 'true' : 'false');
            });
        }

        function pick(suggestion) {
            input.value = suggestion.label;
            clearResults();
            onPick(suggestion);
        }

        function render(suggestions, query, pickTheFirst) {
            clearResults();

            if (!suggestions.length) {
                return;
            }

            if (pickTheFirst) {
                pick(suggestions[0]);

                return;
            }

            suggestions.forEach(function (suggestion) {
                var item = document.createElement('li');

                item.textContent = suggestion.label;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', 'false');
                item.addEventListener('click', function () {
                    pick(suggestion);
                });
                list.appendChild(item);
            });

            shown = suggestions;
            shownFor = query;
            list.hidden = false;
        }

        function search(query, pickTheFirst) {
            var url = options.url
                + (options.url.indexOf('?') === -1 ? '?' : '&')
                + 'q=' + encodeURIComponent(query)
                + '&limit=' + RESULT_LIMIT
                + (options.country ? '&country=' + encodeURIComponent(options.country) : '');

            // Only the answer to what the field says now is wanted, a late one to an older
            // question would replace it.
            if (pending) {
                pending.abort();
            }
            pending = new window.AbortController();

            window.fetch(url, { headers: { Accept: 'application/json' }, signal: pending.signal })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Address search responded with ' + response.status);
                    }

                    return response.json();
                })
                .then(function (data) {
                    render(data.suggestions || [], query, pickTheFirst);
                })
                .catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    // A failing address search must not break the page, the map stays usable.
                    clearResults();
                    if (window.console) {
                        window.console.warn('Address search failed:', error);
                    }
                });
        }

        function stopSearching() {
            window.clearTimeout(timer);
            if (pending) {
                pending.abort();
                pending = null;
            }
        }

        input.addEventListener('input', function () {
            var query = input.value.trim();

            stopSearching();

            if (query.length < MIN_QUERY_LENGTH) {
                clearResults();

                return;
            }

            timer = window.setTimeout(function () {
                search(query);
            }, DEBOUNCE_MS);
        });

        input.addEventListener('keydown', function (event) {
            var query = input.value.trim();

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                if (!shown.length) {
                    return;
                }
                event.preventDefault();
                highlight(event.key === 'ArrowDown'
                    ? (highlighted + 1) % shown.length
                    : (highlighted <= 0 ? shown.length : highlighted) - 1);
            } else if (event.key === 'Enter') {
                // The field may stand inside a form, which Enter would submit.
                event.preventDefault();

                if (shown.length && shownFor === query) {
                    pick(shown[highlighted >= 0 ? highlighted : 0]);
                } else if (query.length >= MIN_QUERY_LENGTH) {
                    // Typed faster than the suggestions came, the first one answering is taken.
                    stopSearching();
                    search(query, true);
                }
            } else if (event.key === 'Escape') {
                clearResults();
            }
        });

        document.addEventListener('click', function (event) {
            if (!control.getContainer().contains(event.target)) {
                clearResults();
            }
        });

        return control;
    }

    window.MapsPlugin = window.MapsPlugin || {};
    window.MapsPlugin.addressSearch = addressSearch;
})(window, document);
