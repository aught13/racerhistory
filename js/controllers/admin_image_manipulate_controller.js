import { Controller } from "@hotwired/stimulus";

import CropSelector from "../lib/crop_selector.js";

export default class extends Controller {
    static targets = [
        "image",
        "canvas",
        "cropX",
        "cropY",
        "cropWidth",
        "cropHeight",
        "rotateRange",
        "rotateInput",
        "aspectButton",
        "ratioFree",
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
                onCropChange: (crop) => this.updateCropInputs(crop),
            },
        );

        this.markActiveRatioButton(
            this.hasRatioFreeTarget ? this.ratioFreeTarget : null,
        );
    }

    disconnect() {
        this.selector = null;
    }

    setAspectRatio(event) {
        event.preventDefault();

        const raw = event.params.ratio;
        const ratio =
            raw === "free" || raw === "" || raw === undefined
                ? null
                : Number(raw);
        const nextRatio = Number.isFinite(ratio) ? ratio : null;

        if (this.selector) {
            this.selector.setAspectRatio(nextRatio);
        }

        this.markActiveRatioButton(event.currentTarget);
    }

    setRotation(event) {
        event.preventDefault();
        const degrees = Number(event.params.degrees ?? 0);
        this.applyRotation(degrees);
    }

    syncRotateRange(event) {
        this.applyRotation(Number(event.currentTarget.value || 0), "range");
    }

    syncRotateInput(event) {
        this.applyRotation(Number(event.currentTarget.value || 0), "input");
    }

    reset(event) {
        event.preventDefault();

        if (this.hasRotateRangeTarget) {
            this.rotateRangeTarget.value = "0";
        }
        if (this.hasRotateInputTarget) {
            this.rotateInputTarget.value = "0";
        }

        if (this.selector) {
            this.selector.setAspectRatio(null);
            this.selector.setRotation(0);

            if (this.imageTarget.complete && this.imageTarget.naturalWidth) {
                this.selector.setCropBox(
                    0,
                    0,
                    this.imageTarget.naturalWidth,
                    this.imageTarget.naturalHeight,
                );
            }
        }

        this.markActiveRatioButton(
            this.hasRatioFreeTarget ? this.ratioFreeTarget : null,
        );
    }

    applyRotation(degrees, source) {
        const value = String(Number.isFinite(degrees) ? degrees : 0);

        if (this.hasRotateRangeTarget && source !== "range") {
            this.rotateRangeTarget.value = value;
        }
        if (this.hasRotateInputTarget && source !== "input") {
            this.rotateInputTarget.value = value;
        }

        if (this.selector) {
            this.selector.setRotation(parseFloat(value) || 0);
        }
    }

    markActiveRatioButton(activeButton) {
        this.aspectButtonTargets.forEach((button) =>
            button.classList.remove("active"),
        );
        if (activeButton) {
            activeButton.classList.add("active");
        }
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
}
