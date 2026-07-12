import { Controller } from "@hotwired/stimulus";

import {
    cleanupGamesPage,
    initGamesPage,
    resetGamesSearchRuntimeState,
} from "../lib/games_search_runtime.js";

function getInit() {
    if (typeof window !== "undefined") {
        const override = window.__GAMES_SEARCH_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initGamesPage;
}

function getCleanup() {
    if (typeof window !== "undefined") {
        const override = window.__GAMES_SEARCH_CLEANUP__;
        if (typeof override === "function") {
            return override;
        }
    }

    return cleanupGamesPage;
}

function getReset() {
    if (typeof window !== "undefined") {
        const override = window.__GAMES_SEARCH_RESET__;
        if (typeof override === "function") {
            return override;
        }
    }

    return resetGamesSearchRuntimeState;
}

export default class extends Controller {
    connect() {
        getInit()();
    }

    disconnect() {
        getCleanup()();
        getReset()();
    }
}
