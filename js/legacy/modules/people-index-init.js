/* people-index-init.js (ES module)
 * Initializer for People index DataTable + name search.
 */
export default function initPeopleIndex(options = {}) {
    const tableSelector = options.tableSelector || "#people-table";
    const searchInputSelector =
        options.searchInputSelector || "#people-name-search";
    const dataUrl = options.dataUrl || "";
    const { dataTableOptions: userDataTableOptions } = options;
    const useServerSide = Boolean(dataUrl);

    if (
        typeof window.$ === "undefined" ||
        typeof window.$.fn === "undefined" ||
        (typeof window.$.fn.DataTable !== "function" &&
            typeof window.$.fn.dataTable !== "function")
    ) {
        return { table: null };
    }

    const $table = window.$(tableSelector);
    const tableEl = $table.get(0);

    if (!$table.length) {
        return { table: null };
    }

    let peopleTable = null;
    if (window.$.fn.dataTable?.isDataTable?.($table.get(0)) === true) {
        try {
            peopleTable = $table.DataTable();
        } catch {
            peopleTable = null;
        }
    }

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

        window.$(".dt-button-collection").remove();
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
        return { table: peopleTable };
    } catch (err) {
        console.debug(err);
        return { table: null };
    }
}
