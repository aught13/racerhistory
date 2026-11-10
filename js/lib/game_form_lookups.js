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
const activeGameFormLookups = new WeakMap();

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
 * @param {ParentNode} scope
 * @returns {{ setSelected: function, clearSelection: function, refresh: function, destroy: function }}
 */
export function initLookup(config, scope = document) {
    const searchInput = scope?.querySelector(`#${config.searchInputId}`);
    const hiddenInput = scope?.querySelector(`#${config.hiddenInputId}`);
    const resultsContainer = scope?.querySelector(`#${config.resultsTableId}`);
    const selectedDisplay = scope?.querySelector(
        `#${config.selectedDisplayId}`,
    );

    if (!searchInput || !hiddenInput || !resultsContainer) {
        return {
            setSelected: () => {},
            clearSelection: () => {},
            refresh: () => {},
            destroy: () => {},
        };
    }

    let debounceTimer = null;
    let abortController = null;
    const ownerDocument = searchInput.ownerDocument;

    function setSelected(id, displayText) {
        hiddenInput.value = String(id);
        if (selectedDisplay) {
            const badge = ownerDocument.createElement("span");
            badge.className = "badge bg-primary me-2";
            badge.append(
                ownerDocument.createTextNode(String(displayText ?? "")),
            );

            const clearButton = ownerDocument.createElement("button");
            clearButton.type = "button";
            clearButton.className = "btn-close btn-close-white ms-1";
            clearButton.setAttribute("aria-label", "Clear");
            badge.append(" ", clearButton);
            selectedDisplay.replaceChildren(badge);
        }
        resultsContainer.replaceChildren();
        searchInput.value = "";
        if (config.onSelect) {
            config.onSelect(id, displayText);
        }
    }

    function clearSelection() {
        hiddenInput.value = "";
        if (selectedDisplay) {
            const emptyState = ownerDocument.createElement("span");
            emptyState.className = "text-muted fst-italic";
            emptyState.textContent = "None selected";
            selectedDisplay.replaceChildren(emptyState);
        }
        if (config.onSelect) {
            config.onSelect(null, null);
        }
    }

    function handleSelectionClick(event) {
        if (event.target?.closest?.(".btn-close")) {
            clearSelection();
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
        const requestController = new AbortController();
        abortController = requestController;

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
            signal: requestController.signal,
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((data) => {
                if (abortController !== requestController) {
                    return;
                }
                if (data.success) {
                    buildTable(data.results);
                } else {
                    const message = ownerDocument.createElement("div");
                    message.className = "text-danger small";
                    message.textContent = "Search error";
                    resultsContainer.replaceChildren(message);
                }
            })
            .catch((err) => {
                if (
                    err.name !== "AbortError" &&
                    abortController === requestController
                ) {
                    const message = ownerDocument.createElement("div");
                    message.className = "text-danger small";
                    message.textContent = "Network error";
                    resultsContainer.replaceChildren(message);
                }
            })
            .finally(() => {
                if (abortController === requestController) {
                    abortController = null;
                }
            });
    }

    function handleSearchInput() {
        clearTimeout(debounceTimer);
        debounceTimer = null;
        const q = searchInput.value.trim();
        if (q.length < MIN_QUERY_LENGTH) {
            abortController?.abort();
            abortController = null;
            resultsContainer.replaceChildren();
            return;
        }
        debounceTimer = setTimeout(() => doSearch(q), DEBOUNCE_MS);
    }

    function handleDocumentClick(e) {
        if (
            !searchInput.contains(e.target) &&
            !resultsContainer.contains(e.target)
        ) {
            resultsContainer.replaceChildren();
        }
    }

    searchInput.addEventListener("input", handleSearchInput);
    selectedDisplay?.addEventListener("click", handleSelectionClick);
    ownerDocument.addEventListener("click", handleDocumentClick);

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
        destroy: () => {
            clearTimeout(debounceTimer);
            abortController?.abort();
            searchInput.removeEventListener("input", handleSearchInput);
            selectedDisplay?.removeEventListener("click", handleSelectionClick);
            ownerDocument.removeEventListener("click", handleDocumentClick);
            searchInput.value = "";
            resultsContainer.replaceChildren();
        },
    };
}

/**
 * Initialize all game form lookups.
 */
export function initGameFormLookups(
    formContainer = document.querySelector("#game-form-card"),
) {
    if (!formContainer) {
        return null;
    }
    const existingCleanup = activeGameFormLookups.get(formContainer);
    if (existingCleanup) {
        return existingCleanup;
    }

    // URLs are stored as data attributes on the form container
    const opponentSearchUrl = formContainer.dataset.opponentSearchUrl;
    const placeSearchUrl = formContainer.dataset.placeSearchUrl;
    const siteSearchUrl = formContainer.dataset.siteSearchUrl;
    const gameTypeSearchUrl = formContainer.dataset.gameTypeSearchUrl;
    const ownerDocument = formContainer.ownerDocument;
    const siteModal = ownerDocument.getElementById("add-site-modal");
    const opponentModal = ownerDocument.getElementById("add-opponent-modal");
    const opponentPlaceModal = ownerDocument.getElementById(
        "add-opponent-place-modal",
    );

    const lookups = [];
    const popupEventHandlers = [];
    let siteLookup = null;

    // Game Type lookup
    const gameTypeLookup = initLookup(
        {
            searchInputId: "game-type-search",
            hiddenInputId: "game-type-id",
            resultsTableId: "game-type-results",
            selectedDisplayId: "game-type-selected",
            searchUrl: gameTypeSearchUrl,
            columns: ["game_type_name", "abr"],
            columnLabels: ["Name", "Abbr"],
            displayField: "game_type_name",
            initialDisplay: formContainer.dataset.gameTypeDisplay || null,
        },
        formContainer,
    );
    lookups.push(gameTypeLookup);

    // Opponent lookup
    const opponentLookup = initLookup(
        {
            searchInputId: "opponent-search",
            hiddenInputId: "opponent-id",
            resultsTableId: "opponent-results",
            selectedDisplayId: "opponent-selected",
            searchUrl: opponentSearchUrl,
            columns: ["opponent_name", "opponent_short", "opponent_mascot"],
            columnLabels: ["Name", "Short", "Mascot"],
            displayField: "label",
            initialDisplay: formContainer.dataset.opponentDisplay || null,
        },
        formContainer,
    );
    lookups.push(opponentLookup);

    // Place lookup
    const placeLookup = initLookup(
        {
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
                const addSiteBtn = formContainer.querySelector("#add-site-btn");
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
                const sitePopupPlaceId = siteModal?.querySelector(
                    "#add-site-modal-place_id",
                );
                if (sitePopupPlaceId) {
                    sitePopupPlaceId.value = id || "";
                }
            },
        },
        formContainer,
    );
    lookups.push(placeLookup);

    // Site lookup
    siteLookup = initLookup(
        {
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
                const placeId = formContainer.querySelector("#place-id");
                return { place_id: placeId ? placeId.value : "" };
            },
        },
        formContainer,
    );
    lookups.push(siteLookup);

    // Place lookup inside the opponent popup modal
    const opponentPlaceLookup = initLookup(
        {
            searchInputId: "opponent-place-search",
            hiddenInputId: "add-opponent-modal-place_id",
            resultsTableId: "opponent-place-results",
            selectedDisplayId: "opponent-place-selected",
            searchUrl: placeSearchUrl,
            columns: ["place_city", "place_state"],
            columnLabels: ["Locality", "Subdivision"],
            displayField: "place_city",
        },
        opponentModal,
    );
    lookups.push(opponentPlaceLookup);

    function handleNewOption(lookup) {
        return (event) => {
            const option = event.detail?.newOption;
            if (option?.value !== undefined && option?.value !== null) {
                lookup.setSelected(option.value, option.text ?? "");
            }
        };
    }

    for (const [eventName, lookup] of [
        ["admin:game-type-added", gameTypeLookup],
        ["admin:game-opponent-added", opponentLookup],
        ["admin:game-opponent-place-added", opponentPlaceLookup],
        ["admin:game-place-added", placeLookup],
        ["admin:game-site-added", siteLookup],
    ]) {
        const handler = handleNewOption(lookup);
        ownerDocument.addEventListener(eventName, handler);
        popupEventHandlers.push([eventName, handler]);
    }

    // Initial state: disable "Add Site" if no place selected
    const placeId = formContainer.querySelector("#place-id");
    const addSiteBtn = formContainer.querySelector("#add-site-btn");
    if (addSiteBtn && placeId && !placeId.value) {
        addSiteBtn.disabled = true;
        addSiteBtn.title = "Select a place first";
    }

    let opponentModalTogglingToPlace = false;
    const opponentAddPlaceBtn = opponentModal?.querySelector(
        "#opponent-add-place-btn",
    );

    function handleOpponentModalHidden() {
        if (!opponentModalTogglingToPlace) {
            opponentPlaceLookup.clearSelection();
        }
    }

    function handleShowOpponentPlaceModal() {
        opponentModal.removeEventListener(
            "hidden.bs.modal",
            handleShowOpponentPlaceModal,
        );
        opponentModalTogglingToPlace = false;
        ownerDocument.defaultView.bootstrap.Modal.getOrCreateInstance(
            opponentPlaceModal,
        ).show();
    }

    function handleOpponentPlaceModalHidden() {
        ownerDocument.defaultView.bootstrap.Modal.getOrCreateInstance(
            opponentModal,
        ).show();
    }

    function handleOpponentAddPlaceClick(event) {
        event.preventDefault();
        event.stopPropagation();
        opponentModalTogglingToPlace = true;
        opponentModal.addEventListener(
            "hidden.bs.modal",
            handleShowOpponentPlaceModal,
        );
        ownerDocument.defaultView.bootstrap.Modal.getOrCreateInstance(
            opponentModal,
        ).hide();
    }

    if (opponentModal) {
        opponentModal.addEventListener(
            "hidden.bs.modal",
            handleOpponentModalHidden,
        );
    }
    if (opponentPlaceModal) {
        opponentPlaceModal.addEventListener(
            "hidden.bs.modal",
            handleOpponentPlaceModalHidden,
        );
    }
    if (opponentAddPlaceBtn && opponentModal && opponentPlaceModal) {
        opponentAddPlaceBtn.addEventListener(
            "click",
            handleOpponentAddPlaceClick,
        );
    }

    let destroyed = false;
    const cleanup = () => {
        if (destroyed) {
            return;
        }
        destroyed = true;
        activeGameFormLookups.delete(formContainer);
        lookups.forEach((lookup) => lookup.destroy());
        popupEventHandlers.forEach(([eventName, handler]) => {
            ownerDocument.removeEventListener(eventName, handler);
        });
        opponentModal?.removeEventListener(
            "hidden.bs.modal",
            handleOpponentModalHidden,
        );
        opponentModal?.removeEventListener(
            "hidden.bs.modal",
            handleShowOpponentPlaceModal,
        );
        opponentPlaceModal?.removeEventListener(
            "hidden.bs.modal",
            handleOpponentPlaceModalHidden,
        );
        opponentAddPlaceBtn?.removeEventListener(
            "click",
            handleOpponentAddPlaceClick,
        );
    };
    activeGameFormLookups.set(formContainer, cleanup);

    return cleanup;
}
