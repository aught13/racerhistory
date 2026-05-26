import { Controller } from "@hotwired/stimulus";

import CropSelector from "../lib/crop_selector.js";

export default class extends Controller {
    static values = {
        aspectRatio: Number,
    };

    static targets = [
        "image",
        "canvas",
        "cropX",
        "cropY",
        "cropWidth",
        "cropHeight",
    ];

    connect() {
        if (!this.hasImageTarget || !this.hasCanvasTarget) {
            return;
        }

        if (!this.imageTarget.id || !this.canvasTarget.id) {
            return;
        }

        this.selector = new CropSelector(
            this.canvasTarget.id,
            this.imageTarget.id,
            {
                aspectRatio: this.aspectRatio,
                onCropChange: (crop) => this.updateCropInputs(crop),
            },
        );
    }

    disconnect() {
        this.selector = null;
    }

    reset() {
        if (!this.selector) {
            return;
        }

        this.selector.setAspectRatio(this.aspectRatio);
    }

    updateCropInputs(crop) {
        if (this.hasCropXTarget) {
            this.cropXTarget.value = String(crop.x);
        }
        if (this.hasCropYTarget) {
            this.cropYTarget.value = String(crop.y);
        }
        if (this.hasCropWidthTarget) {
            this.cropWidthTarget.value = String(crop.width);
        }
        if (this.hasCropHeightTarget) {
            this.cropHeightTarget.value = String(crop.height);
        }
    }

    get aspectRatio() {
        return this.hasAspectRatioValue ? this.aspectRatioValue : 1400 / 720;
    }
}
