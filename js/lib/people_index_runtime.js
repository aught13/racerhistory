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
                    const initOptions = {
                        tableSelector: "#people-table",
                        searchInputSelector: "#people-name-search",
                        dataUrl: dataUrl || undefined,
                    };

                    // Attempt initialization, retrying a few times if the
                    // first attempt doesn't produce a DataTable (race with
                    // loader timing or Turbo cache restores can cause this).
                    const attemptInit = () => {
                        try {
                            return (
                                initPeopleIndex(initOptions) || { table: null }
                            );
                        } catch (err) {
                            console.debug(err);
                            return { table: null };
                        }
                    };

                    const result = attemptInit();
                    if (result && result.table) {
                        try {
                            if (
                                typeof window !== "undefined" &&
                                typeof window.addEventListener === "function"
                            ) {
                                window.addEventListener(
                                    "turbo:before-cache",
                                    cleanupPeopleIndexPage,
                                    { once: true },
                                );
                            }
                        } catch {
                            // no-op
                        }
                        return result;
                    }

                    const backoffs = [150, 300, 600, 1200];
                    let attempt = 0;

                    return new Promise((resolve) => {
                        const retry = () => {
                            attempt += 1;
                            const r = attemptInit();
                            if (r && r.table) {
                                try {
                                    if (
                                        typeof window !== "undefined" &&
                                        typeof window.addEventListener ===
                                            "function"
                                    ) {
                                        window.addEventListener(
                                            "turbo:before-cache",
                                            cleanupPeopleIndexPage,
                                            { once: true },
                                        );
                                    }
                                } catch {
                                    // no-op
                                }
                                resolve(r);
                                return;
                            }
                            if (attempt >= backoffs.length) {
                                resolve(r);
                                return;
                            }
                            window.setTimeout(retry, backoffs[attempt - 1]);
                        };

                        window.setTimeout(retry, backoffs[0]);
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
    // Also clear any search binding marker so re-initialization (e.g. after
    // Turbo-backed navigation) can re-bind the input event listeners.
    try {
        const input = document.querySelector("#people-name-search");
        if (input && input.dataset && input.dataset.peopleSearchBound) {
            delete input.dataset.peopleSearchBound;
        }
    } catch {
        // no-op
    }
}
