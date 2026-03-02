// ...existing code...
/* Loader for seasons init (ES module).
 * Imports the initializer module and boots it on DOM/turbo load events.
 */
import { ensureSearchBuilderLoaded } from "./modules/searchbuilder-loader.mjs";

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
 * @param {number} maxAttempts - Maximum number of attempts
 * @param {number} delayMs - Delay between attempts in milliseconds
 * @returns {Promise<boolean>} - True when DataTables is available
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

function waitForDataTables(maxAttempts = 100, delayMs = 100) {
    return new Promise((resolve) => {
        let attempts = 0;

        function check() {
            if (isDataTablesAvailable()) {
                resolve(true);
                return;
            }

            attempts += 1;
            if (attempts >= maxAttempts) {
                console.warn(
                    "DataTables did not load after " +
                        maxAttempts * delayMs +
                        "ms",
                );
                resolve(false);
                return;
            }

            setTimeout(check, delayMs);
        }

        check();
    });
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

// Enhanced boot with DataTables availability check
async function enhancedBoot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (!frame || frame.id !== "seasons-table-frame") {
            return;
        }
    }

    console.debug(
        `[seasons-init-loader] Boot event triggered: ${event?.type || "initial"}`,
    );

    // Check if DataTables is available before attempting init
    const hasDataTables = await waitForDataTables();
    if (!hasDataTables) {
        console.warn("Skipping seasons-init: DataTables not available on page");
        return;
    }

    console.debug(
        "[seasons-init-loader] DataTables available, initializing...",
    );
    boot(event);
}

document.addEventListener("DOMContentLoaded", enhancedBoot);
document.addEventListener("turbo:load", enhancedBoot);
document.addEventListener("turbo:frame-load", enhancedBoot);
