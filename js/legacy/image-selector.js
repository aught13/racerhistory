/**
 * Image Selector Modal Handler
 *
 * Provides functionality for browsing, selecting, and uploading images with cropping.
 * Integrates with the image_selector_modal.php element.
 */

class ImageSelector {
    constructor(modalId) {
        this.modalId = modalId;
        this.modal = document.getElementById(modalId);
        if (!this.modal) {
            console.error("Modal not found:", modalId);
            return;
        }

        this.config = window.imageSelectorConfig?.[modalId] || {};
        this.targetField = document.getElementById(this.config.targetFieldId);
        this.selectedImageId = null;
        this.selectedImage = null;
        this.cropper = null;
        this.loadedImages = [];
        this.selectedFile = null;
        this.tagForm = null;
        this.skipCropToggle = null;
        this.searchDebounce = null;
        // Default aspect ratio is 1 (square), can be overridden via config.aspectRatio; null = free
        this.aspectRatio =
            typeof this.config.aspectRatio === "number" &&
            isFinite(this.config.aspectRatio)
                ? this.config.aspectRatio
                : null;

        this.initElements();
        this.bindEvents();
    }

    initElements() {
        // Tab elements
        this.selectTab = document.getElementById(`${this.modalId}-select-tab`);
        this.uploadTab = document.getElementById(`${this.modalId}-upload-tab`);

        // Gallery elements
        this.searchInput = document.getElementById(`${this.modalId}-search`);
        this.gallery = document.getElementById(`${this.modalId}-gallery`);

        // Upload elements
        this.fileInput = document.getElementById(`${this.modalId}-file-input`);
        this.cropContainer = document.getElementById(
            `${this.modalId}-crop-container`,
        );
        this.cropImage = document.getElementById(`${this.modalId}-crop-image`);
        this.cropPreview = document.getElementById(
            `${this.modalId}-crop-preview`,
        );
        this.noPreview = document.getElementById(`${this.modalId}-no-preview`);
        this.cropControls = document.getElementById(
            `${this.modalId}-crop-controls`,
        );
        this.tagForm = document.getElementById(`${this.modalId}-tag-form`);
        this.skipCropToggle = document.getElementById(
            `${this.modalId}-skip-crop`,
        );

        // Buttons
        this.selectBtn = document.getElementById(`${this.modalId}-select-btn`);
        this.uploadBtn = document.getElementById(`${this.modalId}-upload-btn`);
        this.rotateLeftBtn = document.getElementById(
            `${this.modalId}-rotate-left`,
        );
        this.rotateRightBtn = document.getElementById(
            `${this.modalId}-rotate-right`,
        );
        this.resetCropBtn = document.getElementById(
            `${this.modalId}-reset-crop`,
        );
    }

    bindEvents() {
        // Modal shown event - load images
        this.modal.addEventListener("shown.bs.modal", () =>
            this.onModalShown(),
        );

        // Tab switching
        this.selectTab?.addEventListener("shown.bs.tab", () =>
            this.onSelectTabShown(),
        );
        this.uploadTab?.addEventListener("shown.bs.tab", () =>
            this.onUploadTabShown(),
        );

        // Search (debounced server-backed search when typing)
        this.searchInput?.addEventListener("input", (e) =>
            this.onSearch(e.target.value),
        );

        // File selection
        this.fileInput?.addEventListener("change", (e) =>
            this.onFileSelected(e),
        );

        // Crop controls
        this.rotateLeftBtn?.addEventListener("click", () =>
            this.cropper?.rotate(-90),
        );
        this.rotateRightBtn?.addEventListener("click", () =>
            this.cropper?.rotate(90),
        );
        this.resetCropBtn?.addEventListener("click", () =>
            this.cropper?.reset(),
        );

        // Action buttons
        this.selectBtn?.addEventListener("click", () => this.onSelectImage());
        this.uploadBtn?.addEventListener("click", () => this.onUploadImage());

        // Gallery click delegation
        this.gallery?.addEventListener("click", (e) => {
            const card = e.target.closest("[data-image-id]");
            if (card) {
                this.onGalleryImageClick(card);
            }
        });
    }

    onModalShown() {
        // Load images when modal opens (select tab is default)
        this.loadImages();
        // Ensure select button is visible since select tab is default active
        this.selectBtn.style.display = "inline-block";
        this.uploadBtn.style.display = "none";
    }

    onSelectTabShown() {
        this.selectBtn.style.display = "inline-block";
        this.uploadBtn.style.display = "none";
        if (this.loadedImages.length === 0) {
            this.loadImages();
        }
    }

    onUploadTabShown() {
        this.selectBtn.style.display = "none";
        this.uploadBtn.style.display = "inline-block";
    }

    async loadImages() {
        this.gallery.innerHTML =
            '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        try {
            let url = "/admin/images/browse";
            const params = new URLSearchParams();

            if (this.config.tagFilter) {
                params.append("tag", this.config.tagFilter);
            }

            if (params.toString()) {
                url += "?" + params.toString();
            }

            const response = await fetch(url, {
                credentials: "same-origin",
            });

            if (!response.ok) {
                throw new Error("Failed to load images");
            }

            const data = await response.json();
            this.loadedImages = data.images || [];
            this.syncTargetFieldSelection();
            this.renderGallery(this.loadedImages);
        } catch (error) {
            console.error("Error loading images:", error);
            this.gallery.innerHTML =
                '<div class="col-12"><div class="alert alert-danger">Failed to load images</div></div>';
        }
    }

    renderGallery(images) {
        if (!images || images.length === 0) {
            this.gallery.innerHTML =
                '<div class="col-12"><div class="alert alert-info">No images found</div></div>';
            return;
        }

        this.gallery.innerHTML = images
            .map(
                (img) => `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card image-card" data-image-id="${img.id}" style="cursor: pointer;">
                    <img src="${img.thumbnail_url || img.url}" class="card-img-top" alt="Image ${img.id}" style="height: 150px; object-fit: cover;">
                    <div class="card-body p-2">
                        <small class="text-muted">#${img.id}</small>
                        ${
                            img.tags
                                ? `<div class="mt-1">${img.tags
                                      .slice(0, 2)
                                      .map(
                                          (t) =>
                                              `<span class="badge bg-secondary badge-sm">${t}</span>`,
                                      )
                                      .join(" ")}</div>`
                                : ""
                        }
                    </div>
                </div>
            </div>
        `,
            )
            .join("");
    }

    onSearch(query) {
        const q = (query || "").trim();

        // When query is empty, show the initial loaded images
        if (q === "") {
            this.lastSearchQuery = "";
            this.lastLocalFiltered = this.loadedImages
                ? this.loadedImages.slice()
                : [];
            this.renderGallery(this.loadedImages);
            return;
        }

        // Perform immediate local filtering so tests and quick UX remain
        // responsive even if server search is unavailable. Server search
        // will run in the background and refresh results when ready.
        const qLower = q.toLowerCase();
        const localFiltered = (this.loadedImages || []).filter((img) => {
            // match by exact id
            if (String(img.id) === q) return true;
            if (
                img.original_name &&
                String(img.original_name).toLowerCase().includes(qLower)
            )
                return true;
            if (
                Array.isArray(img.tags) &&
                img.tags.join(" ").toLowerCase().includes(qLower)
            )
                return true;
            return false;
        });

        this.lastSearchQuery = q;
        this.lastLocalFiltered = localFiltered;
        this.renderGallery(localFiltered);

        // Debounce server requests to update with authoritative results
        if (this.searchDebounce) clearTimeout(this.searchDebounce);
        this.searchDebounce = setTimeout(
            () => this.performServerSearch(q),
            300,
        );
    }

    async performServerSearch(q) {
        if (!this.gallery) return;

        try {
            let url = "/admin/images/browse";
            const params = new URLSearchParams();
            if (this.config.tagFilter) {
                params.append("tag", this.config.tagFilter);
            }
            params.append("q", q);
            // Increase limit for searches to return more matches
            params.append("limit", "500");

            url += "?" + params.toString();

            const response = await fetch(url, { credentials: "same-origin" });
            if (!response.ok) throw new Error("Failed to search images");
            const data = await response.json();
            this.loadedImages = data.images || [];
            this.syncTargetFieldSelection();
            this.renderGallery(this.loadedImages);
        } catch (err) {
            console.error("Search error:", err);
            // On failure, leave the immediate local results in place so tests
            // and UX don't get interrupted. If we have a cached local result,
            // re-render it.
            if (this.lastLocalFiltered) {
                this.renderGallery(this.lastLocalFiltered);
            }
        }
    }

    onGalleryImageClick(card) {
        // Remove previous selection
        this.gallery
            .querySelectorAll(".image-card")
            .forEach((c) =>
                c.classList.remove("border", "border-primary", "border-3"),
            );

        // Add selection to clicked card
        card.classList.add("border", "border-primary", "border-3");
        this.selectedImageId = parseInt(card.dataset.imageId, 10);
        this.selectedImage =
            this.loadedImages.find(
                (img) => Number(img.id) === this.selectedImageId,
            ) || null;
    }

    onSelectImage() {
        if (!this.selectedImageId) {
            alert("Please select an image");
            return;
        }

        // Set the target field value
        if (this.targetField) {
            this.targetField.value = this.selectedImageId;
            this.applySelectedImageData(this.selectedImage);

            // Trigger change event for any listeners
            this.targetField.dispatchEvent(
                new Event("change", { bubbles: true }),
            );
        }

        // Close modal
        const bsModal = bootstrap.Modal.getInstance(this.modal);
        bsModal?.hide();
    }

    syncTargetFieldSelection() {
        if (!this.targetField) {
            return;
        }

        const currentId = parseInt(this.targetField.value || "", 10);
        if (!Number.isFinite(currentId) || currentId <= 0) {
            return;
        }

        const selectedImage =
            this.loadedImages.find((img) => Number(img.id) === currentId) ||
            null;
        this.applySelectedImageData(selectedImage);
    }

    applySelectedImageData(image) {
        if (!this.targetField) {
            return;
        }

        if (image && image.url) {
            this.targetField.dataset.selectedImageUrl = image.url;
        } else {
            delete this.targetField.dataset.selectedImageUrl;
        }

        if (image && image.thumbnail_url) {
            this.targetField.dataset.selectedImageThumbnailUrl =
                image.thumbnail_url;
        } else {
            delete this.targetField.dataset.selectedImageThumbnailUrl;
        }

        if (image && image.hero_url) {
            this.targetField.dataset.selectedImageHeroUrl = image.hero_url;
        } else {
            delete this.targetField.dataset.selectedImageHeroUrl;
        }
    }

    onFileSelected(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        this.selectedFile = file;
        if (this.skipCropToggle) {
            this.skipCropToggle.checked = false;
        }

        // Destroy existing cropper
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }

        // Read file and initialize cropper
        const reader = new FileReader();
        reader.onload = (e) => {
            this.cropImage.src = e.target.result;
            this.cropContainer.style.display = "block";
            this.cropPreview.style.display = "block";
            this.noPreview.style.display = "none";
            this.cropControls.style.display = "block";

            // Initialize Cropper.js with configurable aspect ratio
            this.cropper = new Cropper(this.cropImage, {
                aspectRatio: this.aspectRatio,
                viewMode: 1,
                preview: this.cropPreview,
                autoCropArea: 0.8,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        };
        reader.readAsDataURL(file);
    }

    async onUploadImage() {
        if (!this.selectedFile) {
            alert("Please select an image first");
            return;
        }

        this.uploadBtn.disabled = true;
        this.uploadBtn.textContent = "Uploading...";

        try {
            const skipCrop = this.skipCropToggle?.checked;
            let uploadBlob = null;
            const uploadName = this.selectedFile.name || "upload.jpg";

            if (!skipCrop && this.cropper) {
                // Calculate canvas dimensions based on aspect ratio when configured
                let canvasWidth = 800;
                let canvasHeight = 800;
                const cropOptions = {};
                if (
                    this.aspectRatio &&
                    this.aspectRatio !== 0 &&
                    isFinite(this.aspectRatio)
                ) {
                    if (this.aspectRatio > 1) {
                        canvasHeight = Math.round(
                            canvasWidth / this.aspectRatio,
                        );
                    } else {
                        canvasWidth = Math.round(
                            canvasHeight * this.aspectRatio,
                        );
                    }
                    cropOptions.width = canvasWidth;
                    cropOptions.height = canvasHeight;
                }

                const canvas = this.cropper.getCroppedCanvas(
                    Object.keys(cropOptions).length ? cropOptions : undefined,
                );

                uploadBlob = await new Promise((resolve) =>
                    canvas.toBlob(resolve, "image/jpeg", 0.9),
                );
            } else {
                uploadBlob = this.selectedFile;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append("upload", uploadBlob, uploadName);

            if (this.tagForm) {
                const tagData = new FormData(this.tagForm);
                for (const [name, value] of tagData.entries()) {
                    formData.append(name, value);
                }
            }

            // Add context if provided (for auto-tagging)
            if (!uploadBlob) {
                throw new Error("Unable to prepare image");
            }

            if (this.config.uploadContext) {
                formData.append(
                    "context",
                    JSON.stringify(this.config.uploadContext),
                );
            }

            // Upload
            const response = await fetch("/admin/images/upload", {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers: {
                    "X-CSRF-Token":
                        document.querySelector('meta[name="csrfToken"]')
                            ?.content || "",
                },
            });

            if (!response.ok) {
                throw new Error("Upload failed");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || "Upload failed");
            }

            // Set the target field value
            if (this.targetField) {
                this.targetField.value = data.image.id;
                this.targetField.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
            }

            // Close modal
            const bsModal = bootstrap.Modal.getInstance(this.modal);
            bsModal?.hide();
        } catch (error) {
            console.error("Upload error:", error);
            alert("Upload failed: " + error.message);
        } finally {
            this.uploadBtn.disabled = false;
            this.uploadBtn.textContent = "Upload & Crop";
        }
    }
}

// Auto-initialize image selectors when DOM is ready or after Turbo Drive navigation
function initImageSelectors() {
    const modals = document.querySelectorAll('[id$="-image-selector"]');
    modals.forEach((modal) => {
        // Don't double-init the same modal instance
        if (!modal._imageSelectorInstance) {
            modal._imageSelectorInstance = new ImageSelector(modal.id);
        }
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initImageSelectors);
} else {
    initImageSelectors();
}
// Re-init after every Turbo Drive navigation (modal elements are replaced)
document.addEventListener("turbo:load", function () {
    // Clear stale instance markers so fresh modals get new instances
    document
        .querySelectorAll('[id$="-image-selector"]')
        .forEach((modal) => delete modal._imageSelectorInstance);
    initImageSelectors();
});

// Export for testing
if (typeof module !== "undefined" && module.exports) {
    module.exports = ImageSelector;
}
