/* Loader for seasons init (ES module).
 * Imports the initializer module and boots it on DOM/turbo load events.
 */
import { ensureSearchBuilderLoaded } from "./modules/searchbuilder-loader.mjs";

const DATATABLES_CORE_SRC =
    "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js";
const DATATABLES_BOOTSTRAP_SRC =
    "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js";

let initSeasonsPromise;

function getInitSeasons() {
    const globalRef =
        typeof globalThis !== "undefined" ? globalThis : undefined;
    const windowRef = typeof window !== "undefined" ? window : undefined;
    const mockInit =
        (globalRef && globalRef.__SEASONS_INIT_LOADER_MOCK__) ||
        (windowRef && windowRef.__SEASONS_INIT_LOADER_MOCK__);
    if (typeof mockInit === "function") {
        return Promise.resolve(mockInit);
    }
    if (!initSeasonsPromise) {
        initSeasonsPromise = import("./modules/seasons-init.js").then(
            (mod) => mod.default,
        );
    }
    return initSeasonsPromise;
}

function getSearchBuilderLoader() {
    const globalRef =
        typeof globalThis !== "undefined" ? globalThis : undefined;
    const windowRef = typeof window !== "undefined" ? window : undefined;
    const mockLoader =
        (globalRef && globalRef.__SEASONS_SEARCHBUILDER_LOADER_MOCK__) ||
        (windowRef && windowRef.__SEASONS_SEARCHBUILDER_LOADER_MOCK__);
    if (typeof mockLoader === "function") {
        return mockLoader;
    }
    return ensureSearchBuilderLoaded;
}

function inferActiveTable() {
    const splitsTable = document.querySelector("#season-splits-table");
    if (splitsTable) {
        return { view: "splits", table: splitsTable };
    }

    const standardTable = document.querySelector("#seasons-table");
    if (standardTable) {
        return { view: "standard", table: standardTable };
    }

    const frame = document.getElementById("seasons-table-frame");
    return {
        view: frame?.dataset?.seasonsView || "standard",
        table: null,
    };
}

function countTableColumns(tableEl) {
    const headerRow = tableEl?.querySelector("thead tr");
    if (!headerRow) {
        return 0;
    }

    return Array.from(headerRow.children).reduce((total, cell) => {
        const span = Number(cell.getAttribute("colspan")) || 1;
        return total + span;
    }, 0);
}

function buildStandardColumnLabels() {
    const labels = [];
    labels[1] = "Team";
    labels[2] = "Season";
    labels[3] = "Conf";
    labels[4] = "Conf Finish";
    labels[5] = "OW";
    labels[6] = "OL";
    labels[7] = "OPct";
    labels[8] = "CW";
    labels[9] = "CL";
    labels[10] = "CPct";
    labels[11] = "CTW";
    labels[12] = "CTL";
    labels[13] = "CTPct";
    labels[14] = "PW";
    labels[15] = "PL";
    labels[16] = "Type";
    return labels;
}

function buildSplitsColumnLabels(hasTies) {
    const labels = [];
    labels[1] = "Team";
    labels[2] = "Season";

    const groups = [
        { code: "H" },
        { code: "R" },
        { code: "N" },
        { code: "CH" },
        { code: "CR" },
        { code: "CT" },
        { code: "P" },
    ];

    let index = 3;
    groups.forEach((group) => {
        labels[index] = `${group.code}W`;
        index += 1;
        labels[index] = `${group.code}L`;
        index += 1;
        if (hasTies) {
            labels[index] = `${group.code}T`;
            index += 1;
        }
    });

    labels[index] = "Type";
    return labels;
}

function getCellDataAttr(meta, name) {
    const cell =
        meta?.settings?.aoData?.[meta.row]?.anCells?.[meta.col] || null;
    if (!cell) {
        return null;
    }
    const value = cell.getAttribute(`data-${name}`);
    return value !== null ? value : null;
}

function buildOptions() {
    const { view, table } = inferActiveTable();
    if (view === "splits") {
        const frame = document.getElementById("seasons-table-frame");
        const hasTies = frame?.dataset?.splitsHasTies === "true";
        const totalColumns = countTableColumns(table);
        const columns = totalColumns
            ? Array.from({ length: totalColumns - 1 }, (_, index) => index + 1)
            : [];
        return {
            tableSelector: "#season-splits-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
            filterButtonId: "seasons-filter-btn",
            columns,
            columnLabels: buildSplitsColumnLabels(hasTies),
            dataTableOptions: {
                order: [
                    [1, "asc"],
                    [2, "asc"],
                ],
                responsive: false,
            },
        };
    }

    return {
        tableSelector: "#seasons-table",
        controlsSelector: "#seasons-controls",
        panelSelector: "#searchbuilder-panel",
        filterButtonId: "seasons-filter-btn",
        columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
        columnLabels: buildStandardColumnLabels(),
        dataTableOptions: {
            columnDefs: [
                {
                    targets: 16,
                    type: "string",
                    render: function (data, type, row, meta) {
                        if (type === "filter" || type === "search") {
                            const searchValue =
                                getCellDataAttr(meta, "search") ??
                                getCellDataAttr(meta, "filter");
                            return searchValue !== null ? searchValue : data;
                        }
                        return data;
                    },
                },
            ],
        },
    };
}

/**
 * Wait for jQuery and DataTables to be available.
 * @returns {boolean} - True when DataTables is available
 */
function isDataTablesAvailable() {
    if (typeof window.$ === "undefined" || typeof window.$.fn === "undefined") {
        return false;
    }

    const { DataTable, dataTable } = window.$.fn;
    const hasDataTableFn =
        typeof DataTable === "function" || typeof dataTable === "function";
    const hasDataTableObj = dataTable && typeof dataTable === "object";
    return hasDataTableFn || hasDataTableObj;
}

function hasJquery() {
    return typeof window.$ === "function" && typeof window.$.fn === "object";
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            // Script tag already in DOM (static HTML, Turbo head-merge, or
            // previous dynamic injection). If it already executed, resolve.
            // Otherwise wait for its load event.
            if (existing.dataset.loaded === "true") {
                resolve();
                return;
            }
            existing.addEventListener("load", () => resolve());
            existing.addEventListener("error", () =>
                reject(new Error("Failed to load " + src)),
            );
            // If the script already fired load before we attached, resolve
            // on next tick so waitForCondition can confirm the global.
            setTimeout(resolve, 0);
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
        await waitForCondition(hasJquery, 10000, 50);
    }
    if (!isDataTablesAvailable()) {
        await loadScript(DATATABLES_CORE_SRC);
        await loadScript(DATATABLES_BOOTSTRAP_SRC);
        await waitForCondition(isDataTablesAvailable, 5000, 50);
    }
}

function boot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (!frame || frame.id !== "seasons-table-frame") {
            return;
        }
    }

    getInitSeasons()
        .then((initSeasons) => {
            if (typeof initSeasons !== "function") {
                console.warn("seasons-init default export is not a function");
                return;
            }
            const runInit = () => {
                const attemptInit = (remaining) => {
                    const opts = buildOptions();
                    const tableEl = document.querySelector(opts.tableSelector);
                    const controlsEl = document.querySelector(
                        opts.controlsSelector,
                    );
                    const panelEl = document.querySelector(opts.panelSelector);

                    if (!tableEl) {
                        console.debug(
                            `[seasons-init] Table element not found: ${opts.tableSelector}`,
                        );
                    }
                    if (!controlsEl) {
                        console.debug(
                            `[seasons-init] Controls element not found: ${opts.controlsSelector}`,
                        );
                    }
                    if (!panelEl) {
                        console.debug(
                            `[seasons-init] Panel element not found: ${opts.panelSelector}`,
                        );
                    }

                    if (!tableEl || !controlsEl || !panelEl) {
                        if (remaining > 0) {
                            setTimeout(() => {
                                attemptInit(remaining - 1);
                            }, 50);
                        } else {
                            console.warn(
                                "[seasons-init] Required DOM elements not found after retries",
                            );
                        }
                        return;
                    }

                    console.debug(
                        "[seasons-init] All DOM elements found, initializing",
                    );
                    initSeasons(opts);
                };

                // Let the frame DOM settle before initializing DataTables.
                Promise.resolve().then(() => {
                    attemptInit(5);
                });
            };

            const loadSearchBuilder = getSearchBuilderLoader();
            loadSearchBuilder()
                .then(runInit)
                .catch((err) => {
                    console.warn("SearchBuilder failed to load", err);
                    runInit();
                });
        })
        .catch((err) => {
            console.warn("seasons-init boot failed", err);
        });
}

// Enhanced boot with dynamic DataTables loading
async function enhancedBoot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (!frame || frame.id !== "seasons-table-frame") {
            return;
        }
    }

    // For non-frame events, skip if no seasons table is on the page.
    // Frame-load events may fire before content is settled, so we let
    // boot() handle the retry logic in that case.
    if (event?.type !== "turbo:frame-load") {
        const { table } = inferActiveTable();
        if (!table) {
            return;
        }
    }

    console.debug(
        `[seasons-init-loader] Boot event triggered: ${event?.type || "initial"}`,
    );

    try {
        await ensureDataTablesLoaded();
    } catch (err) {
        console.warn("Skipping seasons-init: DataTables failed to load", err);
        return;
    }

    console.debug(
        "[seasons-init-loader] DataTables available, initializing...",
    );
    boot(event);
}

function cleanupSeasonsPage() {
    const selectors = ["#seasons-table", "#season-splits-table"];
    for (const sel of selectors) {
        const table = document.querySelector(sel);
        if (!table || !hasJquery() || !window.$.fn?.dataTable) {
            continue;
        }
        try {
            if (window.$.fn.dataTable.isDataTable(table)) {
                window.$(table).DataTable().destroy(false);
            }
        } catch (err) {
            console.warn("Failed to clean up seasons DataTable", err);
        }
    }
    const panel = document.querySelector("#searchbuilder-panel");
    if (panel) {
        panel.innerHTML = "";
    }
}

export {
    enhancedBoot,
    boot,
    cleanupSeasonsPage,
    buildOptions,
    inferActiveTable,
    countTableColumns,
    buildStandardColumnLabels,
    buildSplitsColumnLabels,
    getCellDataAttr,
    isDataTablesAvailable,
    ensureDataTablesLoaded,
};

document.addEventListener("turbo:before-cache", cleanupSeasonsPage);

document.addEventListener("DOMContentLoaded", enhancedBoot);
document.addEventListener("turbo:load", enhancedBoot);
document.addEventListener("turbo:frame-load", enhancedBoot);

// If this module was loaded during Turbo navigation (turbo:load already fired),
// run immediately so the first visit via Turbo still initialises.
if (document.readyState !== "loading") {
    enhancedBoot();
}
