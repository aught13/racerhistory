/**
 * Stats page initialization loader.
 *
 * Enhances stats pages with DataTables AJAX loading, SearchBuilder filtering,
 * Scroller for virtual infinite scroll, numeric column sorting, and
 * drag-to-scroll on wide tables. Initializes on DOMContentLoaded and turbo:load.
 */

/* CDN URLs for DataTables assets loaded dynamically */
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

/** Stat column header labels that contain numeric data. */
const NUMERIC_COLUMNS = [
    "GP",
    "GS",
    "MIN",
    "FGM",
    "FGA",
    "3PM",
    "3PA",
    "FTM",
    "FTA",
    "ORB",
    "DRB",
    "RB",
    "AST",
    "STL",
    "BS",
    "TRN",
    "PF",
    "PTS",
    "Seasons",
    "#",
];

/** Number of rows visible at a time (Scroller display buffer). */
const SCROLLER_THRESHOLD = 75;

/* ——— Helpers ————————————————————————————————————————— */

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

/**
 * Ensure jQuery + DataTables core + Bootstrap 5 + Scroller + SearchBuilder
 * JS are loaded and ready.
 *
 * @returns {Promise<void>}
 */
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

/* ——— Public functions ———————————————————————————————— */

/**
 * Main entry point. Called on page load.
 */
function initStatsPage() {
    const table = document.getElementById("stats-results-table");
    const cards = document.getElementById("stats-type-cards");

    if (table) {
        initStatsDataTable(table);
    }

    if (cards) {
        initCardHover(cards);
    }
}

/**
 * Initialize DataTables on a stats results table with AJAX data source.
 *
 * - Loads all data via AJAX from the data-ajax-url attribute
 * - Numeric type detection for stat columns (fixes text-based sorting)
 * - SearchBuilder for advanced client-side filtering on the full dataset
 * - Scroller with deferRender for virtual infinite scroll
 * - scrollX for horizontal scroll on wide tables
 *
 * @param {HTMLTableElement} table
 */
function initStatsDataTable(table) {
    const ajaxUrl = table.dataset.ajaxUrl;
    if (!ajaxUrl) {
        return;
    }

    /* Detect numeric column indices from header text */
    const headers = table.querySelectorAll("thead th");
    const numericTargets = [];
    headers.forEach((th, idx) => {
        if (NUMERIC_COLUMNS.includes(th.textContent.trim())) {
            numericTargets.push(idx);
        }
    });

    /* Find PTS column index for default sort */
    let ptsIdx = -1;
    headers.forEach((th, idx) => {
        if (th.textContent.trim() === "PTS") {
            ptsIdx = idx;
        }
    });

    const dtOptions = {
        ajax: { url: ajaxUrl, dataSrc: "data" },
        deferRender: true,
        searching: false,
        ordering: true,
        info: true,
        autoWidth: true,
        scrollX: true,
        scrollY: "65vh",
        scroller: true,
        dom: "rti",
        pageLength: SCROLLER_THRESHOLD,
        order: ptsIdx >= 0 ? [[ptsIdx, "desc"]] : [[0, "desc"]],
        columnDefs: [
            { type: "num", targets: numericTargets },
            { orderSequence: ["desc", "asc"], targets: "_all" },
        ],
        language: {
            info: "Showing _START_ to _END_ of _TOTAL_ rows",
            infoEmpty: "No rows available",
            infoFiltered: "(filtered from _MAX_ total rows)",
            loadingRecords: "Loading stats\u2026",
            processing: "Processing\u2026",
            zeroRecords: "No matching stats found",
        },
    };

    ensureDataTablesLoaded()
        .then(() => {
            if (
                window.$.fn.dataTable &&
                window.$.fn.dataTable.isDataTable(table)
            ) {
                return;
            }
            const dt = window.$(table).DataTable(dtOptions);
            setupStatsSearchBuilderUi(dt, table);
            /* Fix header/body alignment after each draw (scrollX + Bootstrap sort-icon padding mismatch) */
            dt.on("draw.dt", function () {
                fixScrollXHeaderAlignment(dt);
            });
            initDragScroll(table.closest(".table-responsive"));
        })
        .catch((err) => {
            console.warn("Stats DataTables init failed:", err.message);
        });
}

function setupStatsSearchBuilderUi(dt, table) {
    const card = table.closest(".card");
    if (!card || !card.parentNode) {
        return;
    }

    let controls = document.getElementById("stats-controls");
    if (!controls) {
        controls = document.createElement("div");
        controls.id = "stats-controls";
        controls.className =
            "d-flex align-items-center justify-content-end gap-2 mb-2";
        card.parentNode.insertBefore(controls, card);
    }

    let filterBtn = document.getElementById("stats-filter-btn");
    if (!filterBtn) {
        filterBtn = document.createElement("button");
        filterBtn.type = "button";
        filterBtn.id = "stats-filter-btn";
        filterBtn.className = "btn btn-sm btn-outline-secondary";
        filterBtn.innerHTML =
            '<span><i class="bi bi-funnel"></i> Filter</span>';
        filterBtn.setAttribute("aria-expanded", "false");
        controls.appendChild(filterBtn);
    }

    let slot = document.getElementById("stats-searchbuilder-slot");
    if (!slot) {
        slot = document.createElement("div");
        slot.id = "stats-searchbuilder-slot";
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
            filterBtn.setAttribute("aria-expanded", willOpen ? "true" : "false");
        });
        filterBtn.dataset.sbToggleBound = "true";
    }

    new window.$.fn.dataTable.SearchBuilder(dt, {});
    dt.searchBuilder.container().appendTo(window.$(slot));
    dt.searchBuilder.rebuild();
}

/**
 * Fix header/body column alignment for DataTables scrollX.
 *
 * With scrollX + Bootstrap 5, header <th> cells receive 20px right-padding
 * for sort indicators while body <td> cells use only 8px. Both tables use
 * table-layout:auto, so the browser independently distributes the same total
 * width, misaligning columns. This function reads the body's rendered column
 * widths and applies them to the header using table-layout:fixed and
 * box-sizing:border-box so the browser honours the values exactly.
 *
 * @param {object} dt - DataTables API instance
 */
function fixScrollXHeaderAlignment(dt) {
    const settings = dt.settings()[0];
    if (!settings) {
        return;
    }
    const scrollHead = settings.nScrollHead;
    const scrollBody = settings.nScrollBody;
    if (!scrollHead || !scrollBody) {
        return;
    }
    const headTable = scrollHead.querySelector(
        ".dataTables_scrollHeadInner table",
    );
    const bodyFirstRow = scrollBody.querySelector("tbody tr:first-child");
    const headThs = headTable
        ? Array.from(headTable.querySelectorAll("thead th"))
        : [];
    if (!headTable || !bodyFirstRow || headThs.length === 0) {
        return;
    }
    const bodyTds = Array.from(bodyFirstRow.querySelectorAll("td"));
    if (bodyTds.length !== headThs.length) {
        return;
    }

    /* Read body column rendered widths (border-box, auto-layout result) */
    let totalWidth = 0;
    const colWidths = bodyTds.map((td) => {
        const w = td.getBoundingClientRect().width;
        totalWidth += w;
        return w;
    });

    /* Apply body widths to header -- border-box treats each width as the
       full cell size, matching the body cell border-box */
    headThs.forEach((th, i) => {
        th.style.boxSizing = "border-box";
        th.style.width = colWidths[i] + "px";
        th.style.minWidth = colWidths[i] + "px";
    });

    /* Force fixed layout so the browser respects our explicit widths */
    headTable.style.width = totalWidth + "px";
    headTable.style.tableLayout = "fixed";
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
 * Add hover effects to stat type cards on the index page.
 *
 * @param {HTMLElement} container
 */
function initCardHover(container) {
    const cards = container.querySelectorAll(".stat-type-card");
    cards.forEach((card) => {
        card.addEventListener("mouseenter", () => {
            card.classList.add("shadow-sm");
        });
        card.addEventListener("mouseleave", () => {
            card.classList.remove("shadow-sm");
        });
    });
}

// Export for testing
export {
    initStatsPage,
    initStatsDataTable,
    fixScrollXHeaderAlignment,
    initDragScroll,
    initCardHover,
    cleanupStatsPage,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
};

function cleanupStatsPage() {
    const table = document.getElementById("stats-results-table");
    if (!table || !hasJquery() || !window.$.fn?.dataTable) {
        return;
    }

    try {
        if (window.$.fn.dataTable.isDataTable(table)) {
            window.$(table).DataTable().destroy(true);
        }
    } catch (err) {
        console.warn("Failed to clean up stats DataTable", err);
    }

    const slot = document.getElementById("stats-searchbuilder-slot");
    if (slot) {
        slot.remove();
    }

    const controls = document.getElementById("stats-controls");
    if (controls) {
        controls.remove();
    }
}

// Clean up before Turbo caches the page
document.addEventListener("turbo:before-cache", cleanupStatsPage);

// Initialize on page load events
document.addEventListener("DOMContentLoaded", initStatsPage);
document.addEventListener("turbo:load", initStatsPage);

// If this module was loaded during Turbo navigation (turbo:load already fired),
// run immediately so the first visit via Turbo still initialises.
if (document.readyState !== "loading") {
    initStatsPage();
}
