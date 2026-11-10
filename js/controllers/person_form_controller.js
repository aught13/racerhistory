import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["imageField", "imagePreview"];

    static values = {
        initialImageId: String,
        initialPreviewUrl: String,
    };

    connect() {
        this.boundImageChange = () => this.updateImagePreview();

        if (this.hasImageFieldTarget) {
            this.imageFieldTarget.addEventListener(
                "change",
                this.boundImageChange,
            );
            this.updateImagePreview();
        }
    }

    disconnect() {
        if (this.hasImageFieldTarget) {
            this.imageFieldTarget.removeEventListener(
                "change",
                this.boundImageChange,
            );
        }
    }

    previewUrlForField() {
        if (!this.hasImageFieldTarget) {
            return "";
        }

        const imageId = this.imageFieldTarget.value.trim();
        const selectedUrl =
            this.imageFieldTarget.dataset.selectedImageThumbnailUrl ||
            this.imageFieldTarget.dataset.selectedImageUrl ||
            "";

        if (selectedUrl !== "") {
            return selectedUrl;
        }

        if (imageId !== "" && imageId === this.initialImageIdValue) {
            return this.initialPreviewUrlValue || "";
        }

        return "";
    }

    withCacheBust(url) {
        if (!url) {
            return "";
        }

        return url + (url.includes("?") ? "&" : "?") + "_ts=" + Date.now();
    }

    updateImagePreview() {
        if (!this.hasImageFieldTarget || !this.hasImagePreviewTarget) {
            return;
        }

        const imageId = this.imageFieldTarget.value.trim();
        const previewUrl = this.previewUrlForField();
        const hasValidId = Number.isFinite(Number.parseInt(imageId, 10));

        if (imageId !== "" && hasValidId && previewUrl !== "") {
            const previewImg = this.imagePreviewTarget.querySelector("img");
            if (previewImg) {
                previewImg.src = this.withCacheBust(previewUrl);
            }
            this.imagePreviewTarget.style.display = "block";
            return;
        }

        this.imagePreviewTarget.style.display = "none";
    }
}
