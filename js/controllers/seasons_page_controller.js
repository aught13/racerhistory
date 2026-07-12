import { Controller } from "@hotwired/stimulus";

import {
    cleanupSeasonsPage,
    enhancedBoot,
} from "../lib/seasons_page_runtime.js";

function getInit() {
    if (typeof window !== "undefined") {
        const override = window.__SEASONS_PAGE_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return enhancedBoot;
}

function getCleanup() {
    if (typeof window !== "undefined") {
        const override = window.__SEASONS_PAGE_CLEANUP__;
        if (typeof override === "function") {
            return override;
        }
    }

    return cleanupSeasonsPage;
}

export default class extends Controller {
    connect() {
        getInit()();
    }

    disconnect() {
        getCleanup()();
    }
}
