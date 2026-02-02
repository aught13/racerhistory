/* eslint-disable no-empty */ /* CommonJS seasons init wrapper for Jest tests */
(function (global) {
    function initSeasons(opts = {}) {
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

        if (
            typeof global.$ === "undefined" ||
            typeof global.$.fn === "undefined"
        ) {
            throw new Error("jQuery / DataTables not available");
        }

        const $table = global.$(tableSelector);
        const tableEl = $table.get(0);
        if (!$table.length) {
            return { sb: null, table: null };
        }

        let seasonsTable = null;
        if (global.$?.fn?.dataTable?.isDataTable?.($table.get(0)) === true) {
            try {
                seasonsTable = $table.DataTable();
            } catch {}
        }

        function renumberRows() {
            const rows =
                typeof document !== "undefined"
                    ? document.querySelectorAll(`${tableSelector} tbody tr`)
                    : [];
            if (!rows.length) return;
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
        let sbInstance = null;
        const headerSnapshot = columnLabels ? captureHeaderText(tableEl) : null;

        function captureHeaderText(table) {
            if (!table) {
                return null;
            }
            return Array.from(table.querySelectorAll("thead th")).map(
                (th) => (th.textContent !== null ? th.textContent : ""),
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
                if (seasonsTable && typeof seasonsTable.destroy === "function")
                    seasonsTable.destroy();
            } catch {}
            seasonsTable = null;
            try {
                if (sbInstance) {
                    if (typeof sbInstance.destroy === "function")
                        sbInstance.destroy();
                    else if (sbInstance.dom && sbInstance.dom.container)
                        global.$(sbInstance.dom.container).remove();
                    else if (typeof sbInstance.container === "function")
                        global.$(sbInstance.container()).remove();
                }
            } catch {}
            sbInstance = null;
            const panelEl =
                typeof document !== "undefined"
                    ? document.querySelector(panelSelector)
                    : null;
            const existingBtn =
                typeof document !== "undefined"
                    ? document.getElementById(filterButtonId)
                    : null;
            if (existingBtn && existingBtn.parentNode) {
                existingBtn.parentNode.removeChild(existingBtn);
            }
            if (panelEl) {
                panelEl.innerHTML = "";
            }
            global.$(".dt-button-collection").remove();
            global.$("#searchBuilder").remove();
            global.$(panelSelector).empty();
        }

        function trySetupSearchBuilder(dtApi) {
            try {
                const controlsEl =
                    typeof document !== "undefined"
                        ? document.querySelector(controlsSelector)
                        : null;
                const panelEl =
                    typeof document !== "undefined"
                        ? document.querySelector(panelSelector)
                        : null;
                if (!controlsEl || !panelEl) return null;

                let btn =
                    typeof document !== "undefined"
                        ? document.getElementById(filterButtonId)
                        : null;
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
                        btn.setAttribute(
                            "aria-expanded",
                            open ? "true" : "false",
                        );
                        panelEl.classList.toggle("sb-open", open);
                    });
                    btn._sbHandlerAdded = true;
                }

                const hasSearchBuilder =
                    global.$ &&
                    global.$.fn &&
                    global.$.fn.dataTable &&
                    typeof global.$.fn.dataTable.SearchBuilder === "function";
                if (!hasSearchBuilder) {
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
                        global.$(panelSelector).empty();
                    } catch {}
                    try {
                        let containerEl = null;
                        if (typeof sbInstance.container === "function")
                            containerEl = sbInstance.container();
                        else if (sbInstance.dom && sbInstance.dom.container)
                            containerEl = sbInstance.dom.container;
                        if (containerEl)
                            global.$(panelSelector).append(containerEl);
                    } catch {}
                    return sbInstance;
                }

                try {
                    sbInstance = new global.$.fn.dataTable.SearchBuilder(
                        dtApi,
                        { depthLimit: 2, columns: sbColumns },
                    );
                    let containerEl = null;
                    if (
                        sbInstance &&
                        typeof sbInstance.container === "function"
                    )
                        containerEl = sbInstance.container();
                    else if (
                        sbInstance &&
                        sbInstance.dom &&
                        sbInstance.dom.container
                    )
                        containerEl = sbInstance.dom.container;
                    if (containerEl)
                        global.$(panelSelector).append(containerEl);
                    else {
                        const ph = document.createElement("div");
                        ph.className = "p-3 text-muted small";
                        ph.textContent = "Advanced filter not available.";
                        global.$(panelSelector).append(ph);
                    }
                    global.$(panelSelector).addClass("d-none");
                } catch (err) {
                    console.debug(err);
                    sbInstance = null;
                    const ph = document.createElement("div");
                    ph.className = "p-3 text-muted small";
                    ph.textContent = "Advanced filter not available.";
                    try {
                        global.$(panelSelector).append(ph);
                    } catch {}
                }

                return sbInstance;
            } catch (err) {
                console.debug(err);
                return null;
            }
        }

        destroyExisting();

        const dtOptions = Object.assign(
            {
                paging: false,
                info: false,
                autoWidth: false,
                order: [[2, "desc"]],
                responsive: false,
                scrollX: true,
                dom: "rtip",
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
            return { sb: sbInstance, table: seasonsTable };
        } catch (err) {
            console.debug(err);
            return { sb: null, table: null };
        }
    }

    if (typeof module !== "undefined" && module.exports) {
        module.exports = initSeasons;
    }
    if (typeof global !== "undefined") {
        global.initSeasons = initSeasons;
    }
})(typeof window !== "undefined" ? window : global);
