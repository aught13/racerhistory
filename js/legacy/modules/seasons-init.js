/* seasons-init.js (ES module)
 * Testable initializer for the Seasons DataTable + SearchBuilder integration.
 * Import with: `import initSeasons from './modules/seasons-init.js'`.
 */
export default function initSeasons(opts = {}) {
    const tableSelector = opts.tableSelector || "#seasons-table";
    const controlsSelector = opts.controlsSelector || "#seasons-controls";
    const panelSelector = opts.panelSelector || "#searchbuilder-panel";
    const filterButtonId = opts.filterButtonId || "seasons-filter-btn";
    const sbColumns = opts.columns || [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    const columnLabels = Array.isArray(opts.columnLabels)
        ? opts.columnLabels
        : null;
    const numberColumn = Number(opts.numberColumn ?? 0);
    const {
        initComplete: userInitComplete,
        drawCallback: userDrawCallback,
        ...restDataTableOptions
    } = opts.dataTableOptions || {};

    if (typeof window.$ === "undefined" || typeof window.$.fn === "undefined") {
        throw new Error("jQuery / DataTables not available");
    }

    const $table = window.$(tableSelector);
    const tableEl = $table.get(0);

    if (!$table.length) {
        console.debug(
            `[seasons-init] Table not found with selector "${tableSelector}"`,
        );
        return { sb: null, table: null };
    }

    let seasonsTable = null;
    if (window.$.fn.dataTable?.isDataTable?.($table.get(0)) === true) {
        try {
            seasonsTable = $table.DataTable();
        } catch {
            seasonsTable = null;
        }
    }
    let sbInstance = null;
    const headerSnapshot = columnLabels ? captureHeaderText(tableEl) : null;

    function captureHeaderText(table) {
        if (!table) {
            return null;
        }
        return Array.from(table.querySelectorAll("thead th")).map((th) =>
            th.textContent !== null ? th.textContent : "",
        );
    }

    function restoreHeaderText(table, snapshot) {
        if (!table || !Array.isArray(snapshot)) {
            return;
        }
        const applySnapshot = (root) => {
            const headers = root.querySelectorAll("thead th");
            headers.forEach((th, index) => {
                if (snapshot[index] !== undefined) {
                    th.textContent = snapshot[index];
                }
            });
        };

        applySnapshot(table);

        const wrapper = table.closest(".dataTables_wrapper");
        const scrollHeadTable = wrapper?.querySelector(
            ".dataTables_scrollHead table",
        );
        if (scrollHeadTable) {
            applySnapshot(scrollHeadTable);
        }
    }

    function buildColumnDefs(labels) {
        if (!Array.isArray(labels)) {
            return [];
        }
        return labels
            .map((label, index) => {
                if (!label) {
                    return null;
                }
                return { targets: index, title: label };
            })
            .filter(Boolean);
    }

    function destroyExisting() {
        try {
            if (seasonsTable && typeof seasonsTable.destroy === "function") {
                seasonsTable.destroy();
            }
        } catch {
            /* no-op */
        }
        seasonsTable = null;
        try {
            if (sbInstance) {
                if (typeof sbInstance.destroy === "function")
                    sbInstance.destroy();
                else if (sbInstance.dom && sbInstance.dom.container)
                    window.$(sbInstance.dom.container).remove();
                else if (typeof sbInstance.container === "function")
                    window.$(sbInstance.container()).remove();
            }
        } catch {
            /* no-op */
        }
        sbInstance = null;
        const panelEl = document.querySelector(panelSelector);
        const existingBtn = document.getElementById(filterButtonId);
        if (existingBtn && existingBtn.parentNode) {
            existingBtn.parentNode.removeChild(existingBtn);
        }
        if (panelEl) {
            panelEl.innerHTML = "";
        }
        window.$(".dt-button-collection").remove();
        window.$("#searchBuilder").remove();
        window.$(panelSelector).empty();
    }

    function trySetupSearchBuilder(dtApi) {
        try {
            const controlsEl = document.querySelector(controlsSelector);
            const panelEl = document.querySelector(panelSelector);
            if (!controlsEl || !panelEl) return null;

            let btn = document.getElementById(filterButtonId);
            if (!btn) {
                btn = document.createElement("button");
                btn.type = "button";
                btn.id = filterButtonId;
                btn.className = "btn btn-sm btn-outline-secondary";
                btn.innerHTML =
                    '<span><i class="bi bi-funnel"></i> Filter</span>';
                btn.setAttribute("aria-expanded", "false");
                controlsEl.appendChild(btn);
            }

            if (!btn._sbHandlerAdded) {
                btn.addEventListener("click", function () {
                    const open = panelEl.classList.toggle("d-none")
                        ? false
                        : true;
                    btn.setAttribute("aria-expanded", open ? "true" : "false");
                    panelEl.classList.toggle("sb-open", open);
                });
                btn._sbHandlerAdded = true;
            }

            if (typeof window.$?.fn?.dataTable?.SearchBuilder !== "function") {
                if (!panelEl.childElementCount) {
                    const ph = document.createElement("div");
                    ph.className = "p-3 text-muted small";
                    ph.textContent = "Advanced filter not available.";
                    panelEl.appendChild(ph);
                }
                panelEl.classList.add("d-none");
                btn.setAttribute("aria-expanded", "false");
                return null;
            }

            if (sbInstance) {
                try {
                    window.$(panelSelector).empty();
                } catch {
                    /* no-op */
                }
                try {
                    let containerEl = null;
                    if (typeof sbInstance.container === "function")
                        containerEl = sbInstance.container();
                    else if (sbInstance.dom && sbInstance.dom.container)
                        containerEl = sbInstance.dom.container;
                    if (containerEl)
                        window.$(panelSelector).append(containerEl);
                } catch {
                    /* no-op */
                }
                return sbInstance;
            }

            try {
                sbInstance = new window.$.fn.dataTable.SearchBuilder(dtApi, {
                    depthLimit: 2,
                    columns: sbColumns,
                });
                let containerEl = null;
                if (sbInstance && typeof sbInstance.container === "function")
                    containerEl = sbInstance.container();
                else if (
                    sbInstance &&
                    sbInstance.dom &&
                    sbInstance.dom.container
                )
                    containerEl = sbInstance.dom.container;
                if (containerEl) window.$(panelSelector).append(containerEl);
                else {
                    const ph = document.createElement("div");
                    ph.className = "p-3 text-muted small";
                    ph.textContent = "Advanced filter not available.";
                    window.$(panelSelector).append(ph);
                }
                window.$(panelSelector).addClass("d-none");
            } catch (err) {
                console.debug(err);
                sbInstance = null;
                const ph = document.createElement("div");
                ph.className = "p-3 text-muted small";
                ph.textContent = "Advanced filter not available.";
                window.$(panelSelector).append(ph);
            }

            return sbInstance;
        } catch (err) {
            console.debug(err);
            return null;
        }
    }

    function renumberRows() {
        const rows = document.querySelectorAll(`${tableSelector} tbody tr`);
        if (!rows.length) {
            return;
        }
        rows.forEach((row, index) => {
            let cell = row.children[numberColumn];
            if (!cell) {
                cell = row.querySelector("td");
            }
            if (cell) {
                cell.textContent = String(index + 1);
            }
        });
    }

    destroyExisting();

    // Create the DataTable and wire initComplete
    const dtOptions = Object.assign(
        {
            paging: false,
            info: false,
            searching: true,
            processing: true,
            autoWidth: false,
            order: [[2, "desc"]],
            responsive: false,
            scrollX: true,
            scrollY: "60vh",
            scrollCollapse: true,
            deferRender: true,
            dom: "rtip",
            language: {
                processing:
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading\u2026',
            },
            initComplete: function () {
                renumberRows();
                trySetupSearchBuilder(this);
                restoreHeaderText(tableEl, headerSnapshot);
                if (typeof this?.api === "function") {
                    this.api().columns.adjust().draw(false);
                }
                if (typeof userInitComplete === "function") {
                    userInitComplete.call(this);
                }
            },
            drawCallback: function () {
                renumberRows();
                if (typeof userDrawCallback === "function") {
                    userDrawCallback.call(this);
                }
            },
        },
        restDataTableOptions,
    );

    const columnDefs = buildColumnDefs(columnLabels);
    if (columnDefs.length) {
        dtOptions.columnDefs = columnDefs.concat(dtOptions.columnDefs || []);
    }

    try {
        seasonsTable = $table.DataTable(dtOptions);
        // expose for tests
        console.debug(
            `[seasons-init] DataTable initialized successfully for "${tableSelector}"`,
        );
        return { sb: sbInstance, table: seasonsTable };
    } catch (err) {
        console.error(
            `[seasons-init] Failed to initialize DataTable for "${tableSelector}":`,
            err,
        );
        return { sb: null, table: null };
    }
}
