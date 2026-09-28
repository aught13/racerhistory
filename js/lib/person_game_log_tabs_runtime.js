import initPersonGameLogTabs from "../legacy/modules/person-game-log-tabs.mjs";

const GAME_LOG_TABLE_SELECTOR = "[data-person-game-log-table]";
const GAME_LOG_TABLE_OPTIONS = {
    paging: false,
    info: false,
    searching: true,
    order: [],
    responsive: false,
    scrollX: true,
    autoWidth: false,
    dom: "ftip",
};

function getInitializer() {
    if (typeof window !== "undefined") {
        const override = window.__PERSON_GAME_LOG_TABS_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initPersonGameLogTabs;
}

export function initPersonGameLogTabsRoot(root = document) {
    getInitializer()({ root });
}

export function initPersonGameLogTablesRoot(root = document) {
    const $ = globalThis.window?.$;
    if (typeof $ !== "function" || !$.fn?.dataTable) {
        return;
    }

    root.querySelectorAll(GAME_LOG_TABLE_SELECTOR).forEach((table) => {
        if ($.fn.dataTable.isDataTable(table)) {
            return;
        }

        $(table).DataTable(GAME_LOG_TABLE_OPTIONS);
    });
}

export function bootPersonGameLogTabs(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof globalThis.Element) {
            initPersonGameLogTabsRoot(frame);
            return;
        }
    }

    initPersonGameLogTabsRoot(globalThis.document);
}
