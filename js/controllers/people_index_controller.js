import { Controller } from "@hotwired/stimulus";

import {
    cleanupPeopleIndexPage,
    initPeopleIndexPage,
} from "../lib/people_index_runtime.js";

function getInit() {
    if (typeof window !== "undefined") {
        const override = window.__PEOPLE_INDEX_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initPeopleIndexPage;
}

function getCleanup() {
    if (typeof window !== "undefined") {
        const override = window.__PEOPLE_INDEX_CLEANUP__;
        if (typeof override === "function") {
            return override;
        }
    }

    return cleanupPeopleIndexPage;
}

export default class extends Controller {
    connect() {
        void getInit()();
    }

    disconnect() {
        getCleanup()();
    }
}
