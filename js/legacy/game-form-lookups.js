/**
 * Game Form Lookups - AJAX search with dynamic results tables and popover forms
 *
 * Replaces static select dropdowns with type-ahead search inputs and small
 * results tables. Each lookup manages:
 *  - Debounced search queries to AJAX endpoints
 *  - A compact results table showing entity fields
 *  - A hidden input for the selected entity ID
 *  - Visual feedback for the current selection
 *
 * @module game-form-lookups
 */

/**
 * @typedef {Object} LookupConfig
 * @property {string} searchInputId - ID of the text search input
 * @property {string} hiddenInputId - ID of the hidden input for the selected ID
 * @property {string} resultsTableId - ID of the results table container
 * @property {string} selectedDisplayId - ID of the element showing the current selection
 * @property {string} searchUrl - AJAX search endpoint URL
 * @property {string[]} columns - Column keys to display in results
 * @property {string[]} columnLabels - Human labels for columns
 * @property {string} displayField - The field to show when an item is selected
 * @property {function} [onSelect] - Callback when an item is selected
 * @property {function} [getExtraParams] - Returns extra query params for search
 */

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 1;

/**
 * Escape HTML to prevent XSS in dynamic table content.
 *
 * @param {string} str - Raw string
 * @returns {string} Escaped string
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    const div = document.createElement("div");
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

/**
 * Initialize a single entity lookup.
 *
 * @param {LookupConfig} config
 * @returns {{ setSelected: function, clearSelection: function, refresh: function }}
 */
export function initLookup(config) {
    const searchInput = document.getElementById(config.searchInputId);
    const hiddenInput = document.getElementById(config.hiddenInputId);
    const resultsContainer = document.getElementById(config.resultsTableId);
    const selectedDisplay = document.getElementById(config.selectedDisplayId);

    if (!searchInput || !hiddenInput || !resultsContainer) {
        return {
            setSelected: () => {},
            clearSelection: () => {},
            refresh: () => {},
        };
    }

    let debounceTimer = null;
    let abortController = null;

    function setSelected(id, displayText) {
        hiddenInput.value = id;
        if (selectedDisplay) {
            selectedDisplay.innerHTML =
                '<span class="badge bg-primary me-2">' +
                escapeHtml(displayText) +
                ' <button type="button" class="btn-close btn-close-white ms-1" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';
            const clearBtn = selectedDisplay.querySelector(".btn-close");
            if (clearBtn) {
                clearBtn.addEventListener("click", clearSelection);
            }
        }
        resultsContainer.innerHTML = "";
        searchInput.value = "";
        if (config.onSelect) {
            config.onSelect(id, displayText);
        }
    }

    function clearSelection() {
        hiddenInput.value = "";
        if (selectedDisplay) {
            selectedDisplay.innerHTML =
                '<span class="text-muted fst-italic">None selected</span>';
        }
        if (config.onSelect) {
            config.onSelect(null, null);
        }
    }

    function buildTable(results) {
        if (!results || results.length === 0) {
            resultsContainer.innerHTML =
                '<div class="text-muted small py-1">No results found</div>';
            return;
        }

        let html =
            '<table class="table table-sm table-hover table-striped mb-0 lookup-results-table">';
        html += "<thead><tr>";
        for (const label of config.columnLabels) {
            html += '<th class="small">' + escapeHtml(label) + "</th>";
        }
        html += "<th></th></tr></thead><tbody>";

        for (const row of results) {
            html +=
                '<tr class="lookup-result-row" role="button" style="cursor:pointer"';
            html += ' data-id="' + escapeHtml(String(row.id)) + '"';
            html +=
                ' data-display="' +
                escapeHtml(String(row[config.displayField] || row.id)) +
                '">';
            for (const col of config.columns) {
                const val = row[col];
                html += '<td class="small">' + escapeHtml(val) + "</td>";
            }
            html +=
                '<td><button type="button" class="btn btn-sm btn-outline-primary select-btn">Select</button></td>';
            html += "</tr>";
        }

        html += "</tbody></table>";
        resultsContainer.innerHTML = html;

        // Attach click handlers
        resultsContainer
            .querySelectorAll(".lookup-result-row")
            .forEach((tr) => {
                tr.addEventListener("click", function () {
                    setSelected(this.dataset.id, this.dataset.display);
                });
            });
    }

    function doSearch(query) {
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        let url = config.searchUrl + "?q=" + encodeURIComponent(query);
        if (config.getExtraParams) {
            const extra = config.getExtraParams();
            for (const [k, v] of Object.entries(extra)) {
                if (v !== null && v !== undefined && v !== "") {
                    url +=
                        "&" +
                        encodeURIComponent(k) +
                        "=" +
                        encodeURIComponent(v);
                }
            }
        }

        fetch(url, {
            signal: abortController.signal,
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    buildTable(data.results);
                } else {
                    resultsContainer.innerHTML =
                        '<div class="text-danger small">Search error</div>';
                }
            })
            .catch((err) => {
                if (err.name !== "AbortError") {
                    resultsContainer.innerHTML =
                        '<div class="text-danger small">Network error</div>';
                }
            });
    }

    searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < MIN_QUERY_LENGTH) {
            resultsContainer.innerHTML = "";
            return;
        }
        debounceTimer = setTimeout(() => doSearch(q), DEBOUNCE_MS);
    });

    // Close results when clicking outside
    document.addEventListener("click", function (e) {
        if (
            !searchInput.contains(e.target) &&
            !resultsContainer.contains(e.target)
        ) {
            resultsContainer.innerHTML = "";
        }
    });

    // If there's an initial value, show it
    if (hiddenInput.value && config.initialDisplay) {
        setSelected(hiddenInput.value, config.initialDisplay);
    } else if (!hiddenInput.value) {
        clearSelection();
    }

    return {
        setSelected,
        clearSelection,
        refresh: () => doSearch(searchInput.value.trim()),
    };
}

/**
 * Initialize all game form lookups.
 * Call this on DOMContentLoaded and turbo:load.
 */
export function initGameFormLookups() {
    const formContainer = document.getElementById("game-form-card");
    if (!formContainer) return;

    // initGameFormLookups is wired to both DOMContentLoaded and turbo:load.
    // Guard against duplicate listeners on the same DOM instance.
    if (formContainer.dataset.lookupsInitialized === "true") {
        return;
    }
    formContainer.dataset.lookupsInitialized = "true";

    // URLs are stored as data attributes on the form container
    const opponentSearchUrl = formContainer.dataset.opponentSearchUrl;
    const placeSearchUrl = formContainer.dataset.placeSearchUrl;
    const siteSearchUrl = formContainer.dataset.siteSearchUrl;
    const gameTypeSearchUrl = formContainer.dataset.gameTypeSearchUrl;

    let placeLookup = null;
    let siteLookup = null;

    // Game Type lookup
    initLookup({
        searchInputId: "game-type-search",
        hiddenInputId: "game-type-id",
        resultsTableId: "game-type-results",
        selectedDisplayId: "game-type-selected",
        searchUrl: gameTypeSearchUrl,
        columns: ["game_type_name", "abr"],
        columnLabels: ["Name", "Abbr"],
        displayField: "game_type_name",
        initialDisplay: formContainer.dataset.gameTypeDisplay || null,
    });

    // Opponent lookup
    initLookup({
        searchInputId: "opponent-search",
        hiddenInputId: "opponent-id",
        resultsTableId: "opponent-results",
        selectedDisplayId: "opponent-selected",
        searchUrl: opponentSearchUrl,
        columns: ["opponent_name", "opponent_short", "opponent_mascot"],
        columnLabels: ["Name", "Short", "Mascot"],
        displayField: "opponent_name",
        initialDisplay: formContainer.dataset.opponentDisplay || null,
    });

    // Place lookup
    placeLookup = initLookup({
        searchInputId: "place-search",
        hiddenInputId: "place-id",
        resultsTableId: "place-results",
        selectedDisplayId: "place-selected",
        searchUrl: placeSearchUrl,
        columns: ["place_city", "place_state"],
        columnLabels: ["Locality", "Subdivision"],
        displayField: "place_city",
        initialDisplay: formContainer.dataset.placeDisplay || null,
        onSelect: function (id) {
            // When place changes, clear site selection and update site search context
            if (siteLookup) {
                siteLookup.clearSelection();
            }
            // Update the "Add New Site" button state
            const addSiteBtn = document.getElementById("add-site-btn");
            if (addSiteBtn) {
                if (id) {
                    addSiteBtn.disabled = false;
                    addSiteBtn.title = "Add New Site";
                } else {
                    addSiteBtn.disabled = true;
                    addSiteBtn.title = "Select a place first";
                }
            }
            // Inject place_id into the site popup form
            const sitePopupPlaceId = document.getElementById(
                "add-site-modal-place_id",
            );
            if (sitePopupPlaceId) {
                sitePopupPlaceId.value = id || "";
            }
        },
    });

    // Site lookup
    siteLookup = initLookup({
        searchInputId: "site-search",
        hiddenInputId: "site-id",
        resultsTableId: "site-results",
        selectedDisplayId: "site-selected",
        searchUrl: siteSearchUrl,
        columns: ["site_name", "capacity", "place_city"],
        columnLabels: ["Site", "Capacity", "Place"],
        displayField: "site_name",
        initialDisplay: formContainer.dataset.siteDisplay || null,
        getExtraParams: function () {
            const placeId = document.getElementById("place-id");
            return { place_id: placeId ? placeId.value : "" };
        },
    });

    // Wire up popup form success callbacks
    window.handleOpponentAdded = function (data) {
        if (data.newOption) {
            const oppLookup = document.getElementById("opponent-id");
            if (oppLookup) {
                oppLookup.value = data.newOption.value;
                const display = document.getElementById("opponent-selected");
                if (display) {
                    display.innerHTML =
                        '<span class="badge bg-primary me-2">' +
                        escapeHtml(data.newOption.text) +
                        ' <button type="button" class="btn-close btn-close-white ms-1" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';
                }
            }
        }
    };

    // Place lookup inside the opponent popup modal
    const opponentPlaceLookup = initLookup({
        searchInputId: "opponent-place-search",
        hiddenInputId: "add-opponent-modal-place_id",
        resultsTableId: "opponent-place-results",
        selectedDisplayId: "opponent-place-selected",
        searchUrl: placeSearchUrl,
        columns: ["place_city", "place_state"],
        columnLabels: ["Locality", "Subdivision"],
        displayField: "place_city",
    });

    // Callback when a new place is created from within the opponent popup
    window.handleOpponentPlaceAdded = function (data) {
        if (data.newOption && opponentPlaceLookup) {
            opponentPlaceLookup.setSelected(
                data.newOption.value,
                data.newOption.text,
            );
        }
    };

    window.handlePlaceAdded = function (data) {
        if (data.newOption && placeLookup) {
            placeLookup.setSelected(data.newOption.value, data.newOption.text);
        }
    };

    window.handleSiteAdded = function (data) {
        if (data.newOption && siteLookup) {
            siteLookup.setSelected(data.newOption.value, data.newOption.text);
        }
    };

    window.handleGameTypeAdded = function (data) {
        if (data.newOption) {
            const gtInput = document.getElementById("game-type-id");
            if (gtInput) {
                gtInput.value = data.newOption.value;
                const display = document.getElementById("game-type-selected");
                if (display) {
                    display.innerHTML =
                        '<span class="badge bg-primary me-2">' +
                        escapeHtml(data.newOption.text) +
                        ' <button type="button" class="btn-close btn-close-white ms-1" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';
                }
            }
        }
    };

    // Initial state: disable "Add Site" if no place selected
    const placeId = document.getElementById("place-id");
    const addSiteBtn = document.getElementById("add-site-btn");
    if (addSiteBtn && placeId && !placeId.value) {
        addSiteBtn.disabled = true;
        addSiteBtn.title = "Select a place first";
    }

    // Reset opponent place lookup when opponent modal is hidden normally (not for place toggle)
    const opponentModal = document.getElementById("add-opponent-modal");
    let opponentModalTogglingToPlace = false;
    if (opponentModal && opponentPlaceLookup) {
        opponentModal.addEventListener("hidden.bs.modal", function () {
            if (!opponentModalTogglingToPlace) {
                opponentPlaceLookup.clearSelection();
            }
        });
    }

    // Handle nested modal: opponent → add place
    // Bootstrap doesn't support true nested modals; toggle between them.
    const opponentAddPlaceBtn = document.getElementById(
        "opponent-add-place-btn",
    );
    const opponentPlaceModal = document.getElementById(
        "add-opponent-place-modal",
    );
    if (opponentAddPlaceBtn && opponentModal && opponentPlaceModal) {
        opponentAddPlaceBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            opponentModalTogglingToPlace = true;
            // Hide the opponent modal first, then show the place modal
            const bsOpponentModal =
                bootstrap.Modal.getOrCreateInstance(opponentModal);
            bsOpponentModal.hide();
            opponentModal.addEventListener(
                "hidden.bs.modal",
                function showPlace() {
                    opponentModal.removeEventListener(
                        "hidden.bs.modal",
                        showPlace,
                    );
                    opponentModalTogglingToPlace = false;
                    const bsPlaceModal =
                        bootstrap.Modal.getOrCreateInstance(opponentPlaceModal);
                    bsPlaceModal.show();
                },
            );
        });

        // When the nested place modal closes, re-open the opponent modal
        opponentPlaceModal.addEventListener("hidden.bs.modal", function () {
            const bsOpponentModal =
                bootstrap.Modal.getOrCreateInstance(opponentModal);
            bsOpponentModal.show();
        });
    }
}

// Auto-init on page load (DOMContentLoaded + turbo:load)
function init() {
    initGameFormLookups();
}

if (typeof document !== "undefined") {
    document.addEventListener("DOMContentLoaded", init);
    document.addEventListener("turbo:load", init);
}
