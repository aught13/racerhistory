/* people-index-init.js (ES module)
 * Initializer for People index DataTable + SearchBuilder + name search.
 */
export default function initPeopleIndex(options = {}) {
    const tableSelector = options.tableSelector || "#people-table";
    const controlsSelector = options.controlsSelector || "#people-controls";
    const panelSelector =
        options.panelSelector || "#people-searchbuilder-panel";
    const filterButtonId = options.filterButtonId || "people-filter-btn";
    const searchInputSelector =
        options.searchInputSelector || "#people-name-search";
    const sbColumns = options.columns || [0, 1, 2];
    const dataUrl = options.dataUrl || "";
    const { dataTableOptions: userDataTableOptions } = options;
    const useServerSide = Boolean(dataUrl);

    if (
        typeof window.$ === "undefined" ||
        typeof window.$.fn === "undefined" ||
        (typeof window.$.fn.DataTable !== "function" &&
            typeof window.$.fn.dataTable !== "function")
    ) {
        return { sb: null, table: null };
    }

    const $table = window.$(tableSelector);
    const tableEl = $table.get(0);

    if (!$table.length) {
        return { sb: null, table: null };
    }

    let peopleTable = null;
    if (window.$.fn.dataTable?.isDataTable?.($table.get(0)) === true) {
        try {
            peopleTable = $table.DataTable();
        } catch {
            peopleTable = null;
        }
    }

    let sbInstance = null;

    function destroyExisting() {
        try {
            if (peopleTable && typeof peopleTable.destroy === "function") {
                peopleTable.destroy();
            }
        } catch {
            /* no-op */
        }
        peopleTable = null;

        if (tableEl && tableEl._peopleNameFilterFn) {
            const filters = window.$.fn.dataTable?.ext?.search;
            if (Array.isArray(filters)) {
                const idx = filters.indexOf(tableEl._peopleNameFilterFn);
                if (idx >= 0) {
                    filters.splice(idx, 1);
                }
            }
            delete tableEl._peopleNameFilterFn;
        }

        try {
            if (sbInstance) {
                if (typeof sbInstance.destroy === "function") {
                    sbInstance.destroy();
                } else if (sbInstance.dom && sbInstance.dom.container) {
                    window.$(sbInstance.dom.container).remove();
                } else if (typeof sbInstance.container === "function") {
                    window.$(sbInstance.container()).remove();
                }
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
    }

    function setupSearchBuilder(dtApi) {
        const controlsEl = document.querySelector(controlsSelector);
        const panelEl = document.querySelector(panelSelector);
        if (!controlsEl || !panelEl) {
            return null;
        }

        let btn = document.getElementById(filterButtonId);
        if (!btn) {
            btn = document.createElement("button");
            btn.type = "button";
            btn.id = filterButtonId;
            btn.className = "btn btn-sm btn-outline-secondary";
            btn.innerHTML = '<span><i class="bi bi-funnel"></i> Filter</span>';
            btn.setAttribute("aria-expanded", "false");
            controlsEl.appendChild(btn);
        }

        if (!btn._sbHandlerAdded) {
            btn.addEventListener("click", function () {
                const open = panelEl.classList.toggle("d-none") ? false : true;
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
                if (typeof sbInstance.container === "function") {
                    containerEl = sbInstance.container();
                } else if (sbInstance.dom && sbInstance.dom.container) {
                    containerEl = sbInstance.dom.container;
                }
                if (containerEl) {
                    window.$(panelSelector).append(containerEl);
                }
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
            if (typeof sbInstance.container === "function") {
                containerEl = sbInstance.container();
            } else if (sbInstance.dom && sbInstance.dom.container) {
                containerEl = sbInstance.dom.container;
            }
            if (containerEl) {
                window.$(panelSelector).append(containerEl);
            } else {
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
    }

    function setupNameFilter(dtApi) {
        const input = document.querySelector(searchInputSelector);
        if (!input) {
            return;
        }

        const api = typeof dtApi?.api === "function" ? dtApi.api() : dtApi;
        const tableNode = api?.table?.().node?.() || tableEl;
        if (!tableNode) {
            return;
        }

        if (useServerSide) {
            if (input.dataset.peopleSearchBound !== "true") {
                input.addEventListener("input", () => {
                    if (api && typeof api.search === "function") {
                        api.search(input.value).draw();
                    }
                });
                input.dataset.peopleSearchBound = "true";
            }
            return;
        }

        if (!tableNode._peopleNameFilterFn) {
            const filterFn = function (settings, data, dataIndex) {
                if (settings.nTable !== tableNode) {
                    return true;
                }
                const query = input.value.trim().toLowerCase();
                if (!query) {
                    return true;
                }
                const row = settings.aoData?.[dataIndex]?.nTr || null;
                const searchValue =
                    row?.getAttribute("data-person-search") || data?.[0] || "";
                return String(searchValue).toLowerCase().includes(query);
            };
            const filters = window.$.fn.dataTable?.ext?.search;
            if (Array.isArray(filters)) {
                filters.push(filterFn);
            }
            tableNode._peopleNameFilterFn = filterFn;
        }

        if (input.dataset.peopleSearchBound !== "true") {
            input.addEventListener("input", () => {
                if (api && typeof api.draw === "function") {
                    api.draw();
                }
            });
            input.dataset.peopleSearchBound = "true";
        }
    }

    destroyExisting();

    const dtOptions = Object.assign(
        {
            paging: true,
            pageLength: 50,
            lengthChange: false,
            info: true,
            searching: true,
            order: [[0, "asc"]],
            responsive: false,
            scrollY: "60vh",
            scrollCollapse: true,
            scroller: true,
            deferRender: true,
            autoWidth: false,
            dom: "rtip",
            initComplete: function () {
                setupSearchBuilder(this);
                setupNameFilter(this);
                if (typeof this?.api === "function") {
                    this.api().columns.adjust().draw(false);
                }
            },
            drawCallback: function () {
                setupNameFilter(this);
            },
        },
        userDataTableOptions || {},
    );

    if (useServerSide) {
        dtOptions.serverSide = true;
        dtOptions.processing = true;
        dtOptions.ajax = {
            url: dataUrl,
            dataSrc: "data",
        };
    }

    try {
        peopleTable = $table.DataTable(dtOptions);
        return { sb: sbInstance, table: peopleTable };
    } catch (err) {
        console.debug(err);
        return { sb: null, table: null };
    }
}
