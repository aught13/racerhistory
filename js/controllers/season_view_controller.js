import { Controller } from "@hotwired/stimulus";

import { publicDataTablesReady } from "../lib/public_vite_datatables.mjs";
import { initSeasonViewRoot } from "../lib/season_view_runtime.js";

export default class extends Controller {
    connect() {
        this.onFrameLoad = this.onFrameLoad.bind(this);

        document.addEventListener("turbo:frame-load", this.onFrameLoad);
        this.initializeWhenReady(this.element);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-load", this.onFrameLoad);
    }

    onFrameLoad(event) {
        const frame = event?.target;
        if (frame instanceof globalThis.Element) {
            this.initializeWhenReady(frame);
        }
    }

    initializeWhenReady(root) {
        publicDataTablesReady.then(() => {
            if (root.isConnected) {
                initSeasonViewRoot(root);
            }
        });
    }
}
