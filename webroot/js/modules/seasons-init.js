/* seasons-init.js (ES module)
 * Testable initializer for the Seasons DataTable + SearchBuilder integration.
 * Import with: `import initSeasons from './modules/seasons-init.js'`.
 */
export default function initSeasons(opts = {}) {
    const tableSelector = opts.tableSelector || "#seasons-table";
    const controlsSelector = opts.controlsSelector || "#seasons-controls";
    const panelSelector = opts.panelSelector || "#searchbuilder-panel";
    const sbColumns = opts.columns || [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    if (typeof window.$ === "undefined" || typeof window.$.fn === "undefined") {
        throw new Error("jQuery / DataTables not available");
    }

    const $table = window.$(tableSelector);

    let seasonsTable = null;
    let sbInstance = null;

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
        window.$(".dt-button-collection").remove();
        window.$("#searchBuilder").remove();
        window.$(panelSelector).empty();
    }

    function trySetupSearchBuilder(dtApi) {
        try {
            const controlsEl = document.querySelector(controlsSelector);
            const panelEl = document.querySelector(panelSelector);
            if (!controlsEl || !panelEl) return null;

            let btn = document.getElementById("seasons-filter-btn");
            if (!btn) {
                btn = document.createElement("button");
                btn.type = "button";
                btn.id = "seasons-filter-btn";
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

    destroyExisting();

    // Create the DataTable and wire initComplete
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
                trySetupSearchBuilder(this);
            },
        },
        opts.dataTableOptions || {},
    );

    try {
        seasonsTable = $table.DataTable(dtOptions);
        // expose for tests
        return { sb: sbInstance, table: seasonsTable };
    } catch (err) {
        console.debug(err);
        return { sb: null, table: null };
    }
}
