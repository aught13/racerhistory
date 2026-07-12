import { Controller } from "@hotwired/stimulus";

import { initSeasonViewRoot } from "../lib/season_view_runtime.js";

export default class extends Controller {
    connect() {
        this.onFrameLoad = this.onFrameLoad.bind(this);

        document.addEventListener("turbo:frame-load", this.onFrameLoad);
        initSeasonViewRoot(this.element);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-load", this.onFrameLoad);
    }

    onFrameLoad(event) {
        const frame = event?.target;
        if (frame instanceof globalThis.Element) {
            initSeasonViewRoot(frame);
        }
    }
}
