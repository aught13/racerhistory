function hasJquery() {
    return typeof window.$ === "function" && typeof window.$.fn === "object";
}

function hasDataTables() {
    return (
        typeof window.$?.fn?.DataTable === "function" ||
        typeof window.$?.fn?.dataTable === "function"
    );
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

    // Wait for jQuery to appear on the page for a bounded time, then
    // wait for DataTables. This makes the initializer tolerant of
    // deferred-loading strategies that may register jQuery shortly after
    // page startup (e.g. via route modules).
    const start = Date.now();
    const timeoutMs = 5000;

    return new Promise((resolve, reject) => {
        const check = () => {
            if (hasDataTables()) {
                resolve();
                return;
            }

            if (hasJquery()) {
                // jQuery is available; wait for DataTables specifically.
                waitForDataTables().then(resolve).catch(reject);
                return;
            }

            if (Date.now() - start >= timeoutMs) {
                reject(new Error("jQuery not available"));
                return;
            }

            window.setTimeout(check, 100);
        };

        check();
    });
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

function bindOpponentPickerToggle() {
    const toggle = document.getElementById("series-opponents-picker-toggle");
    if (!toggle) {
        return;
    }

    if (typeof toggle._seriesOpponentsToggleHandler === "function") {
        toggle.removeEventListener(
            "click",
            toggle._seriesOpponentsToggleHandler,
        );
    }

    const handler = () => {
        // Re-fetch elements on each click to avoid stale references
        const panel = document.getElementById("series-opponents-picker-panel");
        if (!panel) {
            console.warn("Series opponents picker panel not found");
            return;
        }

        const currentToggle = document.getElementById(
            "series-opponents-picker-toggle",
        );
        if (!currentToggle) {
            console.warn("Series opponents picker toggle not found");
            return;
        }

        const setExpandedState = (expanded) => {
            currentToggle.setAttribute(
                "aria-expanded",
                expanded ? "true" : "false",
            );
            currentToggle.textContent = expanded
                ? "Hide opponent picker"
                : "Change opponent";
        };

        const isHidden = panel.classList.contains("d-none");
        if (isHidden) {
            panel.classList.remove("d-none");
            setExpandedState(true);
            initSeriesOpponentsTable();
            document.getElementById("series-opponents-search")?.focus();

            return;
        }

        panel.classList.add("d-none");
        setExpandedState(false);
    };

    // Set initial expanded state based on current panel visibility
    const initialPanel = document.getElementById(
        "series-opponents-picker-panel",
    );
    if (initialPanel) {
        const setExpandedState = (expanded) => {
            toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
            toggle.textContent = expanded
                ? "Hide opponent picker"
                : "Change opponent";
        };
        setExpandedState(!initialPanel.classList.contains("d-none"));
    }

    toggle.addEventListener("click", handler);
    toggle._seriesOpponentsToggleHandler = handler;
}

export function initSeriesOpponentsTable() {
    bindOpponentPickerToggle();

    const table = document.getElementById("series-opponents-table");
    if (!table) {
        return;
    }

    const panel = document.getElementById("series-opponents-picker-panel");
    if (panel && panel.classList.contains("d-none")) {
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
                if (typeof existing.columns?.adjust === "function") {
                    existing.columns.adjust();
                }
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

export function cleanupSeriesOpponentsTable() {
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

    const toggle = document.getElementById("series-opponents-picker-toggle");
    if (typeof toggle?._seriesOpponentsToggleHandler === "function") {
        toggle.removeEventListener(
            "click",
            toggle._seriesOpponentsToggleHandler,
        );
        toggle._seriesOpponentsToggleHandler = null;
    }
}
