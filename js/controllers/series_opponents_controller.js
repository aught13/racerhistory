import { Controller } from "@hotwired/stimulus";

import {
    cleanupSeriesOpponentsTable,
    initSeriesOpponentsTable,
} from "../lib/series_opponents_runtime.js";

export default class extends Controller {
    connect() {
        this.boundBeforeCache = () => cleanupSeriesOpponentsTable(this.element);
        document.addEventListener("turbo:before-cache", this.boundBeforeCache);
        initSeriesOpponentsTable(this.element);
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        cleanupSeriesOpponentsTable(this.element);
    }
}
