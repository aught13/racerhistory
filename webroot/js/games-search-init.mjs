/**
 * Games search page initialization loader.
 *
 * Enhances game search pages with DataTables AJAX loading, SearchBuilder filtering,
 * Scroller for virtual infinite scroll, numeric column sorting, and
 * drag-to-scroll on wide tables. Initializes on DOMContentLoaded and turbo:load.
 */

const DATATABLES_CORE_SRC =
    "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js";
const DATATABLES_BOOTSTRAP_SRC =
    "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js";
const DATATABLES_SCROLLER_SRC =
    "https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js";
const SEARCHBUILDER_SRC =
    "https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js";
const SEARCHBUILDER_BOOTSTRAP_SRC =
    "https://cdn.datatables.net/searchbuilder/1.4.2/js/searchBuilder.bootstrap5.min.js";

/** Track tables that are currently being initialized to prevent duplicates */
const initializingTables = new Set();

/** Column headers that contain numeric data. */
const NUMERIC_COLUMNS = [
    "Margin",
    "Pts For",
    "Pts Against",
    "Team Rk",
    "Opp Rk",
    "OT",
    "#",
];

const SCROLLER_THRESHOLD = 75;

function normalizeUrl(url) {
    if (!url) {
        return "";
    }
    try {
        return new URL(url, window.location.origin).toString();
    } catch {
        return String(url);
    }
}

function hasJquery() {
    return typeof window.$ === "function" && typeof window.$.fn === "object";
}

function hasDataTables() {
    return (
        typeof window.$?.fn?.DataTable === "function" ||
        typeof window.$?.fn?.dataTable === "function"
    );
}

function hasSearchBuilder() {
    return typeof window.$?.fn?.dataTable?.SearchBuilder === "function";
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            resolve();
            return;
        }

        const script = document.createElement("script");
        script.src = src;
        script.async = true;
        script.addEventListener("load", () => {
            script.dataset.loaded = "true";
            resolve();
        });
        script.addEventListener("error", () =>
            reject(new Error("Failed to load " + src)),
        );
        document.head.appendChild(script);
    });
}

function waitForCondition(checkFn, timeoutMs, intervalMs) {
    if (checkFn()) {
        return Promise.resolve();
    }
    return new Promise((resolve, reject) => {
        const start = Date.now();
        const tick = () => {
            if (checkFn()) {
                resolve();
                return;
            }
            if (Date.now() - start >= timeoutMs) {
                reject(new Error("Condition timed out"));
                return;
            }
            setTimeout(tick, intervalMs);
        };
        setTimeout(tick, intervalMs);
    });
}

async function ensureDataTablesLoaded() {
    if (!hasJquery()) {
        await waitForCondition(hasJquery, 10000, 50); // Increased from 5000ms to 10000ms
    }
    if (!hasDataTables()) {
        await loadScript(DATATABLES_CORE_SRC);
        await waitForCondition(hasDataTables, 5000, 50); // Increased from 3000ms to 5000ms
    }
    await loadScript(DATATABLES_BOOTSTRAP_SRC);
    await loadScript(DATATABLES_SCROLLER_SRC);
    await loadScript(SEARCHBUILDER_SRC);
    await loadScript(SEARCHBUILDER_BOOTSTRAP_SRC);
    await waitForCondition(hasSearchBuilder, 5000, 50); // Increased from 3000ms to 5000ms
}

/**
 * Main entry point.
 */
function initGamesPage() {
    const table = document.getElementById("games-results-table");
    const cards = document.getElementById("games-type-cards");

    if (table) {
        // Only skip if table already has DataTable AND is in the DOM
        const hasDataTable =
            hasJquery() &&
            window.$.fn.dataTable &&
            window.$.fn.dataTable.isDataTable(table) &&
            document.body.contains(table);

        if (hasDataTable) {
            try {
                const dt = window.$(table).DataTable();
                const nextUrl = normalizeUrl(table.dataset.ajaxUrl || "");
                const currentUrl = normalizeUrl(
                    typeof dt.ajax?.url === "function" ? dt.ajax.url() : "",
                );

                // Turbo navigation can preserve an existing DataTable instance.
                // If the server rendered a different endpoint (e.g. new quick filter),
                // force DataTables to fetch from the new URL.
                if (nextUrl && nextUrl !== currentUrl) {
                    dt.search("");
                    if (typeof dt.searchBuilder?.rebuild === "function") {
                        dt.searchBuilder.rebuild({});
                    }
                    dt.ajax.url(table.dataset.ajaxUrl).load();
                }
            } catch (err) {
                console.warn("Failed to refresh existing games DataTable", err);
            }
        } else if (!initializingTables.has(table.id)) {
            initGamesDataTable(table);
        }
    }

    if (cards) {
        initCardHover(cards);
    }
}

/**
 * Calculate W-L record from table rows.
 * W/L is in column index 6 (7th column).
 *
 * @param {DataTable} dt DataTables instance
 * @returns {string} Record as "W-L"
 */
function calculateRecord(dt) {
    // Get the Result/W-L column index from table data attribute when available.
    let resultColumn = 6;
    if (typeof dt?.table === "function") {
        const tableNode = dt.table()?.node?.();
        if (tableNode?.dataset?.resultColumn) {
            resultColumn = parseInt(tableNode.dataset.resultColumn, 10);
        }
    }

    let wins = 0;
    let losses = 0;

    dt.rows({ search: "applied" })
        .data()
        .each((row) => {
            const result = row[resultColumn];
            if (result === "W") {
                wins++;
            } else if (result === "L") {
                losses++;
            }
        });

    return `${wins}-${losses}`;
}

/**
 * Update the record display with current data.
 *
 * @param {DataTable} dt DataTables instance
 */
function updateRecordDisplay(dt) {
    const record = calculateRecord(dt);
    const recordDisplay = document.getElementById("games-record-display");
    if (recordDisplay) {
        recordDisplay.textContent = "Record: " + record;
    }
}

/**
 * Initialize DataTables on a games results table with AJAX data source.
 *
 * @param {HTMLTableElement} table
 */
function initGamesDataTable(table) {
    if (!table || !table.id) {
        return;
    }

    // Check if this table already has a DataTable instance - if so, skip re-initialization
    if (
        hasJquery() &&
        window.$.fn.dataTable &&
        window.$.fn.dataTable.isDataTable(table)
    ) {
        console.debug(
            `Table ${table.id} already has DataTable instance, skipping re-init`,
        );
        return;
    }

    // Prevent duplicate initialization attempts
    if (initializingTables.has(table.id)) {
        return;
    }

    const ajaxUrl = table.dataset.ajaxUrl;
    if (!ajaxUrl) {
        return;
    }

    // Mark as initializing
    initializingTables.add(table.id);

    const headers = table.querySelectorAll("thead th");
    const numericTargets = [];
    headers.forEach((th, idx) => {
        if (NUMERIC_COLUMNS.includes(th.textContent.trim())) {
            numericTargets.push(idx);
        }
    });

    /* Find Date column for default sort */
    let dateIdx = 0;
    headers.forEach((th, idx) => {
        if (th.textContent.trim() === "Date") {
            dateIdx = idx;
        }
    });

    const dtOptions = {
        ajax: {
            url: ajaxUrl,
            dataSrc: function (json) {
                // Display record if available
                if (json.record) {
                    const recordDisplay = document.getElementById(
                        "games-record-display",
                    );
                    if (recordDisplay) {
                        recordDisplay.textContent = "Record: " + json.record;
                    }
                }
                return json.data;
            },
        },
        deferRender: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: true,
        scrollX: true,
        scrollY: "65vh",
        scroller: true,
        dom: "rti",
        pageLength: SCROLLER_THRESHOLD,
        order: [[dateIdx, "desc"]],
        columnDefs: [
            { type: "html", targets: [dateIdx] },
            { type: "num", targets: numericTargets },
            { orderSequence: ["desc", "asc"], targets: "_all" },
        ],
        language: {
            info: "Showing _START_ to _END_ of _TOTAL_ rows",
            infoEmpty: "No rows available",
            infoFiltered: "(filtered from _MAX_ total rows)",
            loadingRecords: "Loading games\u2026",
            processing: "Processing\u2026",
            zeroRecords: "No matching games found",
        },
    };

    ensureDataTablesLoaded()
        .then(() => {
            // Get fresh reference to the table element to handle Turbo navigation
            const freshTable = document.getElementById("games-results-table");
            if (!freshTable) {
                console.warn("Games table element not found");
                if (table && table.id) {
                    initializingTables.delete(table.id);
                }
                return;
            }

            try {
                const $table = window.$(freshTable);

                // Destroy any existing DataTable instance on this element
                if (
                    window.$.fn.dataTable &&
                    window.$.fn.dataTable.isDataTable(freshTable)
                ) {
                    $table.DataTable().destroy();
                }

                const dt = $table.DataTable(dtOptions);
                setupGamesSearchBuilderUi(dt, freshTable);
                dt.on("draw.dt", function () {
                    dt.columns.adjust();
                    updateRecordDisplay(dt);
                });
                initDragScroll(freshTable.closest(".table-responsive"));

                // Mark initialization as complete
                if (table && table.id) {
                    initializingTables.delete(table.id);
                }
            } catch (err) {
                console.error("Games DataTables initialization error:", err);
                if (table && table.id) {
                    initializingTables.delete(table.id);
                }
            }
        })
        .catch((err) => {
            console.warn("Games DataTables library load failed:", err.message);
            // Clean up on failure
            if (table && table.id) {
                initializingTables.delete(table.id);
            }
        });
}

function setupGamesSearchBuilderUi(dt, table) {
    const card = table.closest(".card");
    if (!card || !card.parentNode) {
        return;
    }

    let controls = document.getElementById("games-controls");
    if (!controls) {
        controls = document.createElement("div");
        controls.id = "games-controls";
        controls.className =
            "d-flex align-items-center justify-content-end gap-2 mb-2";
        card.parentNode.insertBefore(controls, card);
    }

    let filterBtn = document.getElementById("games-filter-btn");
    if (!filterBtn) {
        filterBtn = document.createElement("button");
        filterBtn.type = "button";
        filterBtn.id = "games-filter-btn";
        filterBtn.className = "btn btn-sm btn-outline-secondary";
        filterBtn.innerHTML =
            '<span><i class="bi bi-funnel"></i> Filter</span>';
        filterBtn.setAttribute("aria-expanded", "false");
        controls.appendChild(filterBtn);
    }

    let slot = document.getElementById("games-searchbuilder-slot");
    if (!slot) {
        slot = document.createElement("div");
        slot.id = "games-searchbuilder-slot";
        slot.className = "searchbuilder-panel d-none";
        card.parentNode.insertBefore(slot, card);
    } else {
        slot.classList.add("searchbuilder-panel", "d-none");
        slot.classList.remove("sb-open");
        slot.innerHTML = "";
    }

    if (!filterBtn.dataset.sbToggleBound) {
        filterBtn.addEventListener("click", () => {
            const willOpen = slot.classList.contains("d-none");
            slot.classList.toggle("d-none", !willOpen);
            slot.classList.toggle("sb-open", willOpen);
            filterBtn.setAttribute(
                "aria-expanded",
                willOpen ? "true" : "false",
            );
        });
        filterBtn.dataset.sbToggleBound = "true";
    }

    new window.$.fn.dataTable.SearchBuilder(dt, {});
    dt.searchBuilder.container().appendTo(window.$(slot));
    dt.searchBuilder.rebuild();
}

/**
 * Enable click-and-drag horizontal scrolling on a container.
 *
 * @param {HTMLElement|null} container
 */
function initDragScroll(container) {
    if (!container) {
        return;
    }
    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;

    container.addEventListener("mousedown", (e) => {
        if (e.target.closest("a, button, input, select, textarea")) {
            return;
        }
        isDown = true;
        container.classList.add("is-dragging");
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
    });

    container.addEventListener("mouseleave", () => {
        isDown = false;
        container.classList.remove("is-dragging");
    });

    container.addEventListener("mouseup", () => {
        isDown = false;
        container.classList.remove("is-dragging");
    });

    container.addEventListener("mousemove", (e) => {
        if (!isDown) {
            return;
        }
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        container.scrollLeft = scrollLeft - (x - startX);
    });
}

/**
 * Add hover effects to game type cards on the index page.
 *
 * @param {HTMLElement} container
 */
function initCardHover(container) {
    const cards = container.querySelectorAll(".game-type-card");
    cards.forEach((card) => {
        card.addEventListener("mouseenter", () => {
            card.classList.add("shadow-sm");
        });
        card.addEventListener("mouseleave", () => {
            card.classList.remove("shadow-sm");
        });
    });
}

function cleanupGamesPage() {
    const table = document.getElementById("games-results-table");
    if (!table || !hasJquery() || !window.$.fn?.dataTable) {
        return;
    }

    try {
        if (window.$.fn.dataTable.isDataTable(table)) {
            window.$(table).DataTable().destroy(false);
        }
    } catch (err) {
        console.warn(
            "Failed to clean up games DataTable before navigation",
            err,
        );
    }

    const searchBuilderSlot = document.getElementById(
        "games-searchbuilder-slot",
    );
    if (searchBuilderSlot) {
        searchBuilderSlot.remove();
    }

    const controls = document.getElementById("games-controls");
    if (controls) {
        controls.remove();
    }
}

export {
    initGamesPage,
    initGamesDataTable,
    initDragScroll,
    initCardHover,
    cleanupGamesPage,
    calculateRecord,
    updateRecordDisplay,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
};

// Clear initialization tracking when navigating away
document.addEventListener("turbo:before-fetch", () => {
    cleanupGamesPage();
    initializingTables.clear();
});

document.addEventListener("turbo:before-cache", () => {
    cleanupGamesPage();
    initializingTables.clear();
});

// Initialize on DOMContentLoaded and turbo:load
document.addEventListener("DOMContentLoaded", () => {
    initGamesPage();
});

document.addEventListener("turbo:load", () => {
    initGamesPage();
});

// If this module was loaded during Turbo navigation (turbo:load already fired),
// run immediately so the first visit via Turbo still initialises.
if (document.readyState !== "loading") {
    initGamesPage();
}
