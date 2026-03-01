/* global window, document, console */
import { ensureSearchBuilderLoaded } from "./modules/searchbuilder-loader.mjs";

let initPeoplePromise;
const DATATABLES_CORE_SRC =
    "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js";
const DATATABLES_BOOTSTRAP_SRC =
    "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js";
const DATATABLES_SCROLLER_SRC =
    "https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js";

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
        initPeoplePromise = import("./modules/people-index-init.js").then(
            (mod) => mod.default,
        );
    }
    return initPeoplePromise;
}

function getSearchBuilderLoader() {
    const globalRef =
        typeof globalThis !== "undefined" ? globalThis : undefined;
    const windowRef = typeof window !== "undefined" ? window : undefined;
    const mockLoader =
        (globalRef && globalRef.__PEOPLE_SEARCHBUILDER_LOADER_MOCK__) ||
        (windowRef && windowRef.__PEOPLE_SEARCHBUILDER_LOADER_MOCK__);
    if (typeof mockLoader === "function") {
        return mockLoader;
    }
    return ensureSearchBuilderLoaded;
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

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === "true") {
                resolve();
                return;
            }
            existing.addEventListener("load", () => {
                existing.dataset.loaded = "true";
                resolve();
            });
            existing.addEventListener("error", () =>
                reject(new Error(`Failed to load ${src}`)),
            );
            return;
        }

        const script = document.createElement("script");
        script.src = src;
        script.async = true;
        script.addEventListener("load", () => {
            script.dataset.loaded = "true";
            resolve();
        });
        script.addEventListener("error", () =>
            reject(new Error(`Failed to load ${src}`)),
        );
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

function boot() {
    getInitPeople()
        .then((initPeopleIndex) => {
            if (typeof initPeopleIndex !== "function") {
                console.warn(
                    "people-index-init default export is not a function",
                );
                return;
            }
            const runInit = () => {
                const table = document.querySelector("#people-table");
                const dataUrl = table?.dataset?.peopleDataUrl || "";
                ensureDataTablesLoaded()
                    .then(() =>
                        getSearchBuilderLoader()().catch(() => undefined),
                    )
                    .then(() => {
                        initPeopleIndex({
                            tableSelector: "#people-table",
                            controlsSelector: "#people-controls",
                            panelSelector: "#people-searchbuilder-panel",
                            filterButtonId: "people-filter-btn",
                            searchInputSelector: "#people-name-search",
                            dataUrl: dataUrl || undefined,
                            columns: [0, 1, 2],
                        });
                    })
                    .catch((err) => {
                        console.debug(err);
                    });
            };

            runInit();
        })
        .catch((err) => {
            console.debug(err);
        });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

document.addEventListener("turbo:load", boot);
