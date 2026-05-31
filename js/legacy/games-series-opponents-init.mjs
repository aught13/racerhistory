/**
 * Series opponents list DataTable initializer.
 *
 * Creates a lazy-loaded opponents list with scrollX/scroller and
 * an external text input filter that matches name/abbreviation criteria.
 */

const DATATABLES_CORE_SRC =
    "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js";
const DATATABLES_BOOTSTRAP_SRC =
    "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js";
const DATATABLES_SCROLLER_SRC =
    "https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js";

function hasJquery() {
    return typeof window.$ === "function" && typeof window.$.fn === "object";
}

function hasDataTables() {
    return (
        typeof window.$?.fn?.DataTable === "function" ||
        typeof window.$?.fn?.dataTable === "function"
    );
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
        script.addEventListener("load", resolve);
        script.addEventListener("error", () => {
            reject(new Error(`Failed to load ${src}`));
        });
        document.head.appendChild(script);
    });
}

function waitForDataTables(timeoutMs = 10000, intervalMs = 50) {
    if (hasDataTables()) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const startedAt = Date.now();
        const tick = () => {
            if (hasDataTables()) {
                resolve();
                return;
            }
            if (Date.now() - startedAt >= timeoutMs) {
                reject(new Error("DataTables not available"));
                return;
            }
            window.setTimeout(tick, intervalMs);
        };
        tick();
    });
}

function ensureDataTablesLoaded() {
    if (hasDataTables()) {
        return Promise.resolve();
    }

    if (!hasJquery()) {
        return Promise.reject(new Error("jQuery not available"));
    }

    return loadScript(DATATABLES_CORE_SRC)
        .then(() => loadScript(DATATABLES_BOOTSTRAP_SRC))
        .then(() => loadScript(DATATABLES_SCROLLER_SRC))
        .then(() => waitForDataTables());
}

function bindOpponentSearchInput(dtApi) {
    const input = document.getElementById("series-opponents-search");
    if (!input) {
        return;
    }

    if (input._seriesOpponentsSearchTimer) {
        window.clearTimeout(input._seriesOpponentsSearchTimer);
    }

    if (typeof input._seriesOpponentsSearchHandler === "function") {
        input.removeEventListener("input", input._seriesOpponentsSearchHandler);
    }

    const handler = () => {
        input._seriesOpponentsSearchTimer = window.setTimeout(() => {
            if (typeof dtApi.search === "function") {
                dtApi.search(input.value).draw();
            }
        }, 250);
    };

    input.addEventListener("input", handler);
    input._seriesOpponentsSearchHandler = handler;
}

function initSeriesOpponentsTable() {
    const table = document.getElementById("series-opponents-table");
    if (!table) {
        return;
    }

    const opponentsUrl = table.dataset.opponentsUrl;
    if (!opponentsUrl) {
        return;
    }

    const init = () => {
        try {
            const $table = window.$(table);
            if (window.$.fn.dataTable.isDataTable(table)) {
                const existing = $table.DataTable();
                bindOpponentSearchInput(existing);
                return;
            }

            const dt = $table.DataTable({
                serverSide: true,
                processing: true,
                deferRender: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                scrollX: true,
                scrollY: "55vh",
                scrollCollapse: true,
                scroller: true,
                pageLength: 50,
                searchDelay: 250,
                dom: "rtip",
                order: [[0, "asc"]],
                ajax: {
                    url: opponentsUrl,
                    dataSrc: "data",
                },
                columnDefs: [
                    { targets: [3], orderable: false, searchable: false },
                    { targets: [2], type: "num" },
                ],
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ opponents",
                    infoEmpty: "No opponents available",
                    infoFiltered: "(filtered from _MAX_ total opponents)",
                    loadingRecords: "Loading opponents...",
                    processing: "Loading opponents...",
                    zeroRecords: "No matching opponents found",
                },
                initComplete: function () {
                    bindOpponentSearchInput(this.api());
                },
                drawCallback: function () {
                    if (typeof this.api === "function") {
                        this.api().columns.adjust();
                    }
                },
            });

            bindOpponentSearchInput(dt);
        } catch (err) {
            console.warn("Series opponents DataTable init failed", err);
        }
    };

    ensureDataTablesLoaded()
        .then(init)
        .catch((err) => {
            console.warn("Series opponents DataTables load failed", err);
        });
}

function cleanupSeriesOpponentsTable() {
    const table = document.getElementById("series-opponents-table");
    if (table && hasDataTables() && window.$.fn.dataTable.isDataTable(table)) {
        try {
            window.$(table).DataTable().destroy(false);
        } catch (err) {
            console.warn("Failed to destroy series opponents table", err);
        }
    }

    const input = document.getElementById("series-opponents-search");
    if (!input) {
        return;
    }

    if (input._seriesOpponentsSearchTimer) {
        window.clearTimeout(input._seriesOpponentsSearchTimer);
        input._seriesOpponentsSearchTimer = null;
    }

    if (typeof input._seriesOpponentsSearchHandler === "function") {
        input.removeEventListener("input", input._seriesOpponentsSearchHandler);
        input._seriesOpponentsSearchHandler = null;
    }
}

export { initSeriesOpponentsTable, cleanupSeriesOpponentsTable };

document.addEventListener("turbo:before-fetch", cleanupSeriesOpponentsTable);
document.addEventListener("turbo:before-cache", cleanupSeriesOpponentsTable);
document.addEventListener("DOMContentLoaded", initSeriesOpponentsTable);
document.addEventListener("turbo:load", initSeriesOpponentsTable);

if (document.readyState !== "loading") {
    initSeriesOpponentsTable();
}
