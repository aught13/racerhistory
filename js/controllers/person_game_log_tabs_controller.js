import { Controller } from "@hotwired/stimulus";

import { initPersonGameLogTabsRoot } from "../lib/person_game_log_tabs_runtime.js";

export default class extends Controller {
    connect() {
        this.onFrameLoad = this.onFrameLoad.bind(this);

        document.addEventListener("turbo:frame-load", this.onFrameLoad);
        initPersonGameLogTabsRoot(this.element);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-load", this.onFrameLoad);
    }

    onFrameLoad(event) {
        const frame = event?.target;
        if (frame instanceof globalThis.Element) {
            initPersonGameLogTabsRoot(frame);
        }
    }
}
