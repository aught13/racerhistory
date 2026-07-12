import { Controller } from "@hotwired/stimulus";

import { cleanupStatsPage, initStatsPage } from "../lib/stats_page_runtime.js";

function getInit() {
    if (typeof window !== "undefined") {
        const override = window.__STATS_PAGE_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initStatsPage;
}

function getCleanup() {
    if (typeof window !== "undefined") {
        const override = window.__STATS_PAGE_CLEANUP__;
        if (typeof override === "function") {
            return override;
        }
    }

    return cleanupStatsPage;
}

export default class extends Controller {
    connect() {
        getInit()();
    }

    disconnect() {
        getCleanup()();
    }
}
