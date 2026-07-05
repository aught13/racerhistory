import { Controller } from "@hotwired/stimulus";

import { initGameViewRoot } from "../lib/game_view_runtime.js";

export default class extends Controller {
    connect() {
        this.onFrameLoad = this.onFrameLoad.bind(this);

        document.addEventListener("turbo:frame-load", this.onFrameLoad);
        initGameViewRoot(this.element);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-load", this.onFrameLoad);
    }

    onFrameLoad(event) {
        const frame = event?.target;
        if (frame instanceof globalThis.Element) {
            initGameViewRoot(frame);
        }
    }
}
