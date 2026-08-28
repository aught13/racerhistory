import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.modalId = this.element.id;
        this.modal = this.element;
        this.config = {};
        this.targetField = null;
        this.selectedImageId = null;
        this.selectedImage = null;
        this.cropper = null;
        this.loadedImages = [];
        this.selectedFile = null;
        this.aspectRatio = null;
        this.modalCleanupTimer = null;

        this.refreshConfigAndTargetField();

        this.initElements();
        this.bindEvents();
    }

    disconnect() {
        this.unbindEvents();
        this.destroyCropper();
        if (this.modalCleanupTimer) {
            window.clearTimeout(this.modalCleanupTimer);
            this.modalCleanupTimer = null;
        }
    }

    initElements() {
        this.selectTab = document.getElementById(`${this.modalId}-select-tab`);
        this.uploadTab = document.getElementById(`${this.modalId}-upload-tab`);

        this.searchInput = document.getElementById(`${this.modalId}-search`);
        this.gallery = document.getElementById(`${this.modalId}-gallery`);

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

        this.searchDebounce = null;

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
        this.listeners = {
            shownModal: () => this.onModalShown(),
            hiddenModal: () => this.onModalHidden(),
            shownSelectTab: () => this.onSelectTabShown(),
            shownUploadTab: () => this.onUploadTabShown(),
            searchInput: (event) => this.onSearch(event.target.value),
            fileInput: (event) => this.onFileSelected(event),
            rotateLeft: () => this.cropper?.rotate(-90),
            rotateRight: () => this.cropper?.rotate(90),
            resetCrop: () => this.cropper?.reset(),
            select: () => this.onSelectImage(),
            upload: () => this.onUploadImage(),
            galleryClick: (event) => {
                const card = event.target.closest("[data-image-id]");
                if (card) {
                    this.onGalleryImageClick(card);
                }
            },
        };

        this.modal.addEventListener(
            "shown.bs.modal",
            this.listeners.shownModal,
        );
        this.modal.addEventListener(
            "hidden.bs.modal",
            this.listeners.hiddenModal,
        );
        this.selectTab?.addEventListener(
            "shown.bs.tab",
            this.listeners.shownSelectTab,
        );
        this.uploadTab?.addEventListener(
            "shown.bs.tab",
            this.listeners.shownUploadTab,
        );
        this.searchInput?.addEventListener("input", this.listeners.searchInput);
        this.fileInput?.addEventListener("change", this.listeners.fileInput);
        this.rotateLeftBtn?.addEventListener(
            "click",
            this.listeners.rotateLeft,
        );
        this.rotateRightBtn?.addEventListener(
            "click",
            this.listeners.rotateRight,
        );
        this.resetCropBtn?.addEventListener("click", this.listeners.resetCrop);
        this.selectBtn?.addEventListener("click", this.listeners.select);
        this.uploadBtn?.addEventListener("click", this.listeners.upload);
        this.gallery?.addEventListener("click", this.listeners.galleryClick);
    }

    unbindEvents() {
        if (!this.listeners) {
            return;
        }

        this.modal?.removeEventListener(
            "shown.bs.modal",
            this.listeners.shownModal,
        );
        this.modal?.removeEventListener(
            "hidden.bs.modal",
            this.listeners.hiddenModal,
        );
        this.selectTab?.removeEventListener(
            "shown.bs.tab",
            this.listeners.shownSelectTab,
        );
        this.uploadTab?.removeEventListener(
            "shown.bs.tab",
            this.listeners.shownUploadTab,
        );
        this.searchInput?.removeEventListener(
            "input",
            this.listeners.searchInput,
        );
        this.fileInput?.removeEventListener("change", this.listeners.fileInput);
        this.rotateLeftBtn?.removeEventListener(
            "click",
            this.listeners.rotateLeft,
        );
        this.rotateRightBtn?.removeEventListener(
            "click",
            this.listeners.rotateRight,
        );
        this.resetCropBtn?.removeEventListener(
            "click",
            this.listeners.resetCrop,
        );
        this.selectBtn?.removeEventListener("click", this.listeners.select);
        this.uploadBtn?.removeEventListener("click", this.listeners.upload);
        this.gallery?.removeEventListener("click", this.listeners.galleryClick);

        this.listeners = null;
    }

    destroyCropper() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
    }

    refreshConfigAndTargetField() {
        this.config =
            window.imageSelectorConfig?.[this.modalId] || this.config || {};

        if (this.config?.targetFieldId) {
            this.targetField = document.getElementById(
                this.config.targetFieldId,
            );
        }

        this.aspectRatio =
            typeof this.config?.aspectRatio === "number" &&
            isFinite(this.config.aspectRatio)
                ? this.config.aspectRatio
                : null;
    }

    onModalShown() {
        this.refreshConfigAndTargetField();
        this.loadImages();
        this.toggleActionButtons(true);
    }

    onModalHidden() {
        this.selectedImageId = null;
        this.selectedImage = null;
        this.selectedFile = null;
        if (this.fileInput) {
            this.fileInput.value = "";
        }
        if (this.skipCropToggle) {
            this.skipCropToggle.checked = false;
        }

        this.destroyCropper();

        if (this.cropContainer) {
            this.cropContainer.style.display = "none";
        }
        if (this.cropPreview) {
            this.cropPreview.style.display = "none";
        }
        if (this.noPreview) {
            this.noPreview.style.display = "block";
        }
        if (this.cropControls) {
            this.cropControls.style.display = "none";
        }

        this.gallery
            ?.querySelectorAll(".image-card")
            .forEach((card) =>
                card.classList.remove("border", "border-primary", "border-3"),
            );

        this.cleanupModalArtifacts();
    }

    onSelectTabShown() {
        this.toggleActionButtons(true);
        if (this.loadedImages.length === 0) {
            this.loadImages();
        }
    }

    onUploadTabShown() {
        this.toggleActionButtons(false);
    }

    toggleActionButtons(showSelect) {
        if (this.selectBtn) {
            this.selectBtn.style.display = showSelect ? "inline-block" : "none";
        }
        if (this.uploadBtn) {
            this.uploadBtn.style.display = showSelect ? "none" : "inline-block";
        }
    }

    async loadImages() {
        if (!this.gallery) {
            return;
        }

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
        if (!this.gallery) {
            return;
        }

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
                                          (tag) =>
                                              `<span class="badge bg-secondary badge-sm">${tag}</span>`,
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

        if (q === "") {
            this.lastSearchQuery = "";
            this.lastLocalFiltered = this.loadedImages
                ? this.loadedImages.slice()
                : [];
            this.renderGallery(this.loadedImages);
            return;
        }

        const qLower = q.toLowerCase();
        const localFiltered = (this.loadedImages || []).filter((img) => {
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
            if (this.lastLocalFiltered) {
                this.renderGallery(this.lastLocalFiltered);
            }
        }
    }

    onGalleryImageClick(card) {
        this.gallery
            ?.querySelectorAll(".image-card")
            .forEach((galleryCard) =>
                galleryCard.classList.remove(
                    "border",
                    "border-primary",
                    "border-3",
                ),
            );

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

        this.refreshConfigAndTargetField();

        if (this.targetField) {
            this.targetField.value = this.selectedImageId;
            this.applySelectedImageData(this.selectedImage);
            this.targetField.dispatchEvent(
                new Event("input", { bubbles: true }),
            );
            this.targetField.dispatchEvent(
                new Event("change", { bubbles: true }),
            );
        }

        this.hideModalWithCleanup();
    }

    syncTargetFieldSelection() {
        this.refreshConfigAndTargetField();

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
        if (!file) {
            return;
        }

        this.selectedFile = file;
        if (this.skipCropToggle) {
            this.skipCropToggle.checked = false;
        }

        this.destroyCropper();

        const reader = new FileReader();
        reader.onload = (readerEvent) => {
            if (this.cropImage) {
                this.cropImage.src = readerEvent.target.result;
            }
            if (this.cropContainer) {
                this.cropContainer.style.display = "block";
            }
            if (this.cropPreview) {
                this.cropPreview.style.display = "block";
            }
            if (this.noPreview) {
                this.noPreview.style.display = "none";
            }
            if (this.cropControls) {
                this.cropControls.style.display = "block";
            }

            if (!window.Cropper) {
                console.error("Cropper.js is not available.");
                return;
            }

            this.cropper = new window.Cropper(this.cropImage, {
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

        if (!this.uploadBtn) {
            return;
        }

        this.uploadBtn.disabled = true;
        this.uploadBtn.textContent = "Uploading...";

        try {
            const skipCrop = this.skipCropToggle?.checked;
            let uploadBlob = null;
            const uploadName = this.selectedFile.name || "upload.jpg";

            if (!skipCrop && this.cropper) {
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

            if (!uploadBlob) {
                throw new Error("Unable to prepare image");
            }

            const formData = new FormData();
            formData.append("upload", uploadBlob, uploadName);

            if (this.tagForm) {
                const tagData = new FormData(this.tagForm);
                for (const [name, value] of tagData.entries()) {
                    formData.append(name, value);
                }
            }

            if (this.config.uploadContext) {
                formData.append(
                    "context",
                    JSON.stringify(this.config.uploadContext),
                );
            }

            const csrf = document.querySelector('meta[name="csrfToken"]');
            const response = await fetch("/admin/images/upload", {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers: {
                    "X-CSRF-Token": csrf?.content || "",
                },
            });

            if (!response.ok) {
                throw new Error("Upload failed");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || "Upload failed");
            }

            this.refreshConfigAndTargetField();

            if (this.targetField) {
                this.targetField.value = data.image.id;
                this.applySelectedImageData(data.image || null);
                this.targetField.dispatchEvent(
                    new Event("input", { bubbles: true }),
                );
                this.targetField.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
            }

            this.hideModalWithCleanup();
        } catch (error) {
            console.error("Upload error:", error);
            alert("Upload failed: " + error.message);
        } finally {
            this.uploadBtn.disabled = false;
            this.uploadBtn.textContent = "Upload & Crop";
        }
    }

    hideModalWithCleanup() {
        const modalClass = window.bootstrap?.Modal;
        const modalInstance =
            modalClass?.getInstance(this.modal) ||
            modalClass?.getOrCreateInstance?.(this.modal);

        if (modalInstance?.hide) {
            modalInstance.hide();
            this.scheduleModalArtifactCleanup();

            return;
        }

        if (this.modal) {
            this.modal.classList.remove("show");
            this.modal.style.display = "none";
            this.modal.setAttribute("aria-hidden", "true");
            this.modal.removeAttribute("aria-modal");
        }
        this.cleanupModalArtifacts();
    }

    scheduleModalArtifactCleanup() {
        if (this.modalCleanupTimer) {
            window.clearTimeout(this.modalCleanupTimer);
        }

        // Fallback cleanup in case Bootstrap hide transition does not fully clean body/backdrop state.
        this.modalCleanupTimer = window.setTimeout(() => {
            this.modalCleanupTimer = null;
            this.cleanupModalArtifacts();
        }, 400);
    }

    cleanupModalArtifacts() {
        if (document.querySelector(".modal.show")) {
            return;
        }

        document.body.classList.remove("modal-open");
        document.body.style.removeProperty("overflow");
        document.body.style.removeProperty("padding-right");
        document.querySelectorAll(".modal-backdrop").forEach((backdrop) => {
            backdrop.remove();
        });
    }
}
