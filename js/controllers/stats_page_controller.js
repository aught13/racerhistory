import { Controller } from "@hotwired/stimulus";

import { cleanupStatsPage, initStatsPage } from "../lib/stats_page_runtime.js";

export default class extends Controller {
    connect() {
        this.boundBeforeCache = () => cleanupStatsPage(this.element);
        document.addEventListener("turbo:before-cache", this.boundBeforeCache);
        initStatsPage(this.element);
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        cleanupStatsPage(this.element);
    }
}
