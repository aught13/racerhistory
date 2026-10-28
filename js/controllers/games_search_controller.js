import { Controller } from "@hotwired/stimulus";

import {
    cleanupGamesPage,
    initGamesPage,
    resetGamesSearchRuntimeState,
} from "../lib/games_search_runtime.js";

export default class extends Controller {
    connect() {
        initGamesPage();
    }

    disconnect() {
        cleanupGamesPage();
        resetGamesSearchRuntimeState();
    }
}
