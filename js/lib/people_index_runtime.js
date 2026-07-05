let initPeoplePromise;

function getInitPeople() {
    const globalRef =
        typeof globalThis !== "undefined" ? globalThis : undefined;
    const windowRef = typeof window !== "undefined" ? window : undefined;
    const mockInit =
        (globalRef && globalRef.__PEOPLE_INDEX_INIT_LOADER_MOCK__) ||
        (windowRef && windowRef.__PEOPLE_INDEX_INIT_LOADER_MOCK__);
    if (typeof mockInit === "function") {
        return Promise.resolve(mockInit);
    }
    if (!initPeoplePromise) {
        initPeoplePromise =
            import("../legacy/modules/people-index-init.js").then(
                (mod) => mod.default,
            );
    }
    return initPeoplePromise;
}

function hasDataTables() {
    return (
        typeof window.$?.fn?.DataTable === "function" ||
        typeof window.$?.fn?.dataTable === "function"
    );
}

function hasJquery() {
    return typeof window.$ === "function" && typeof window.$.fn === "object";
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

    return waitForDataTables();
}

export function initPeopleIndexPage() {
    return getInitPeople()
        .then((initPeopleIndex) => {
            if (typeof initPeopleIndex !== "function") {
                console.warn(
                    "people-index-init default export is not a function",
                );
                return;
            }

            const table = document.querySelector("#people-table");
            const dataUrl = table?.dataset?.peopleDataUrl || "";

            return ensureDataTablesLoaded()
                .then(() => {
                    initPeopleIndex({
                        tableSelector: "#people-table",
                        searchInputSelector: "#people-name-search",
                        dataUrl: dataUrl || undefined,
                    });
                })
                .catch((err) => {
                    console.debug(err);
                });
        })
        .catch((err) => {
            console.debug(err);
        });
}

export function cleanupPeopleIndexPage() {
    const table = document.querySelector("#people-table");
    if (!table || !hasJquery() || !window.$.fn?.dataTable) {
        return;
    }

    try {
        if (window.$.fn.dataTable.isDataTable(table)) {
            if (table._peopleNameFilterFn) {
                const filters = window.$.fn.dataTable?.ext?.search;
                if (Array.isArray(filters)) {
                    const idx = filters.indexOf(table._peopleNameFilterFn);
                    if (idx >= 0) {
                        filters.splice(idx, 1);
                    }
                }
                delete table._peopleNameFilterFn;
            }

            window.$(table).DataTable().destroy(false);

            const tbody = table.querySelector("tbody");
            if (tbody) {
                tbody.innerHTML = "";
            }
        }
    } catch (err) {
        console.warn("Failed to clean up people DataTable", err);
    }
}
