import { Controller } from "@hotwired/stimulus";

import {
    cleanupSeriesOpponentsTable,
    initSeriesOpponentsTable,
} from "../lib/series_opponents_runtime.js";

function getInit() {
    if (typeof window !== "undefined") {
        const override = window.__SERIES_OPPONENTS_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initSeriesOpponentsTable;
}

function getCleanup() {
    if (typeof window !== "undefined") {
        const override = window.__SERIES_OPPONENTS_CLEANUP__;
        if (typeof override === "function") {
            return override;
        }
    }

    return cleanupSeriesOpponentsTable;
}

export default class extends Controller {
    connect() {
        getInit()();
    }

    disconnect() {
        getCleanup()();
    }
}
