import { Controller } from "@hotwired/stimulus";

import {
    cleanupSeasonsPage,
    enhancedBoot,
} from "../lib/seasons_page_runtime.js";

export default class extends Controller {
    connect() {
        this.boundBeforeCache = () => cleanupSeasonsPage(this.element);
        document.addEventListener("turbo:before-cache", this.boundBeforeCache);
        enhancedBoot(undefined, this.element);
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        cleanupSeasonsPage(this.element);
    }
}
