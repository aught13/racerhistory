import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        "selectTab",
        "uploadTab",
        "searchInput",
        "gallery",
        "fileInput",
        "cropContainer",
        "cropImage",
        "cropPreview",
        "noPreview",
        "cropControls",
        "tagForm",
        "skipCropToggle",
        "selectButton",
        "uploadButton",
        "rotateLeftButton",
        "rotateRightButton",
        "resetCropButton",
        "dismissButton",
    ];

    static values = {
        targetFieldId: String,
        tagFilter: String,
        uploadContext: Object,
        aspectRatio: String,
        csrfToken: String,
    };

    connect() {
        this.modalId = this.element.id;
        this.modal = this.element;
        this.config = {};
        this.targetForm = null;
        this.targetField = null;
        this.selectedImageId = null;
        this.selectedImage = null;
        this.cropper = null;
        this.loadedImages = [];
        this.selectedFile = null;
        this.aspectRatio = null;
        this.searchDebounce = null;
        this.loadRequest = null;
        this.searchRequest = null;
        this.reader = null;
        this.isActive = true;
        this.isCaching = false;

        this.boundBeforeCache = () => this.resetModalForCache();
        this.element.ownerDocument.addEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        this.refreshConfigAndTargetField();
        this.initElements();
        this.bindEvents();
    }

    disconnect() {
        this.isActive = false;
        this.element.ownerDocument.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        this.unbindEvents();
        this.resetModalForCache();
    }

    initElements() {
        this.selectTab = this.hasSelectTabTarget ? this.selectTabTarget : null;
        this.uploadTab = this.hasUploadTabTarget ? this.uploadTabTarget : null;
        this.searchInput = this.hasSearchInputTarget
            ? this.searchInputTarget
            : null;
        this.gallery = this.hasGalleryTarget ? this.galleryTarget : null;
        this.fileInput = this.hasFileInputTarget ? this.fileInputTarget : null;
        this.cropContainer = this.hasCropContainerTarget
            ? this.cropContainerTarget
            : null;
        this.cropImage = this.hasCropImageTarget ? this.cropImageTarget : null;
        this.cropPreview = this.hasCropPreviewTarget
            ? this.cropPreviewTarget
            : null;
        this.noPreview = this.hasNoPreviewTarget ? this.noPreviewTarget : null;
        this.cropControls = this.hasCropControlsTarget
            ? this.cropControlsTarget
            : null;
        this.tagForm = this.hasTagFormTarget ? this.tagFormTarget : null;
        this.skipCropToggle = this.hasSkipCropToggleTarget
            ? this.skipCropToggleTarget
            : null;
        this.selectBtn = this.hasSelectButtonTarget
            ? this.selectButtonTarget
            : null;
        this.uploadBtn = this.hasUploadButtonTarget
            ? this.uploadButtonTarget
            : null;
        this.rotateLeftBtn = this.hasRotateLeftButtonTarget
            ? this.rotateLeftButtonTarget
            : null;
        this.rotateRightBtn = this.hasRotateRightButtonTarget
            ? this.rotateRightButtonTarget
            : null;
        this.resetCropBtn = this.hasResetCropButtonTarget
            ? this.resetCropButtonTarget
            : null;
        this.dismissBtn = this.hasDismissButtonTarget
            ? this.dismissButtonTarget
            : null;
    }

    bindEvents() {
        this.listeners = {
            showModal: (event) => this.onModalShow(event),
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

        this.modal.addEventListener("show.bs.modal", this.listeners.showModal);
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
            "show.bs.modal",
            this.listeners.showModal,
        );
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
        this.config = {};
        if (this.hasTargetFieldIdValue && this.targetFieldIdValue) {
            this.config.targetFieldId = this.targetFieldIdValue;
        }
        if (this.hasTagFilterValue && this.tagFilterValue) {
            this.config.tagFilter = this.tagFilterValue;
        }
        if (this.hasUploadContextValue) {
            this.config.uploadContext = this.uploadContextValue;
        }

        const aspectRatio =
            this.hasAspectRatioValue && this.aspectRatioValue !== ""
                ? Number(this.aspectRatioValue)
                : null;
        this.aspectRatio = Number.isFinite(aspectRatio) ? aspectRatio : null;
        if (this.aspectRatio !== null) {
            this.config.aspectRatio = this.aspectRatio;
        }

        const formElements = this.targetForm?.elements || [];
        this.targetField = Array.from(formElements).find(
            (field) => field.id === this.config.targetFieldId,
        );
        this.targetField ||= null;
    }

    onModalShow(event) {
        this.targetForm = event?.relatedTarget?.form || null;
        this.refreshConfigAndTargetField();
    }

    onModalShown() {
        this.refreshConfigAndTargetField();
        this.loadImages();
        this.toggleActionButtons(true);
    }

    onModalHidden() {
        this.cancelPendingRequests();
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
            this.cropContainer.classList.add("d-none");
        }
        if (this.cropPreview) {
            this.cropPreview.classList.add("d-none");
            this.cropPreview.replaceChildren();
        }
        if (this.cropImage) {
            this.cropImage.removeAttribute("src");
        }
        if (this.noPreview) {
            this.noPreview.classList.remove("d-none");
        }
        if (this.cropControls) {
            this.cropControls.classList.add("d-none");
        }

        this.gallery
            ?.querySelectorAll(".image-card")
            .forEach((card) =>
                card.classList.remove("border", "border-primary", "border-3"),
            );

        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = null;
        }
        this.targetForm = null;
        this.targetField = null;
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
            this.selectBtn.classList.toggle("d-none", !showSelect);
        }
        if (this.uploadBtn) {
            this.uploadBtn.classList.toggle("d-none", showSelect);
        }
    }

    async loadImages() {
        if (!this.gallery) {
            return;
        }

        this.loadRequest?.abort();
        const request = new AbortController();
        this.loadRequest = request;
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
                signal: request.signal,
            });

            if (!response.ok) {
                throw new Error("Failed to load images");
            }

            const data = await response.json();
            if (request.signal.aborted || !this.isActive) {
                return;
            }
            this.loadedImages = data.images || [];
            this.syncTargetFieldSelection();
            this.renderGallery(this.loadedImages);
        } catch (error) {
            if (error?.name === "AbortError") {
                return;
            }
            console.error("Error loading images:", error);
            this.gallery.innerHTML =
                '<div class="col-12"><div class="alert alert-danger">Failed to load images</div></div>';
        } finally {
            if (this.loadRequest === request) {
                this.loadRequest = null;
            }
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

        const document = this.gallery.ownerDocument;
        const cards = images.map((image) => {
            const column = document.createElement("div");
            column.className = "col-6 col-md-4 col-lg-3";

            const card = document.createElement("div");
            card.className = "card image-card image-selector-gallery-card";
            card.dataset.imageId = String(image.id);

            const preview = document.createElement("img");
            preview.src = image.thumbnail_url || image.url || "";
            preview.className =
                "card-img-top image-selector-gallery-preview object-fit-cover";
            preview.alt = `Image ${image.id}`;
            card.appendChild(preview);

            const body = document.createElement("div");
            body.className = "card-body p-2";

            const idLabel = document.createElement("small");
            idLabel.className = "text-muted";
            idLabel.textContent = `#${image.id}`;
            body.appendChild(idLabel);

            if (Array.isArray(image.tags) && image.tags.length > 0) {
                const tagList = document.createElement("div");
                tagList.className = "mt-1";
                image.tags.slice(0, 2).forEach((tag) => {
                    const badge = document.createElement("span");
                    badge.className = "badge bg-secondary badge-sm";
                    badge.textContent = String(tag);
                    tagList.appendChild(badge);
                });
                body.appendChild(tagList);
            }

            card.appendChild(body);
            column.appendChild(card);

            return column;
        });

        this.gallery.replaceChildren(...cards);
    }

    onSearch(query) {
        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = null;
        }
        this.loadRequest?.abort();
        this.searchRequest?.abort();
        this.searchRequest = null;

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

        this.searchDebounce = window.setTimeout(() => {
            this.searchDebounce = null;
            this.performServerSearch(q);
        }, 300);
    }

    async performServerSearch(q) {
        if (!this.gallery) {
            return;
        }

        this.searchRequest?.abort();
        const request = new AbortController();
        this.searchRequest = request;
        try {
            let url = "/admin/images/browse";
            const params = new URLSearchParams();
            if (this.config.tagFilter) {
                params.append("tag", this.config.tagFilter);
            }
            params.append("q", q);
            params.append("limit", "500");
            url += "?" + params.toString();

            const response = await fetch(url, {
                credentials: "same-origin",
                signal: request.signal,
            });
            if (!response.ok) throw new Error("Failed to search images");
            const data = await response.json();
            if (request.signal.aborted || !this.isActive) {
                return;
            }
            this.loadedImages = data.images || [];
            this.syncTargetFieldSelection();
            this.renderGallery(this.loadedImages);
        } catch (err) {
            if (err?.name === "AbortError") {
                return;
            }
            console.error("Search error:", err);
            if (this.lastLocalFiltered) {
                this.renderGallery(this.lastLocalFiltered);
            }
        } finally {
            if (this.searchRequest === request) {
                this.searchRequest = null;
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

        this.reader?.abort();
        this.selectedFile = file;
        if (this.skipCropToggle) {
            this.skipCropToggle.checked = false;
        }

        this.destroyCropper();

        const reader = new FileReader();
        this.reader = reader;
        reader.onload = (readerEvent) => {
            this.reader = null;
            if (!this.isActive || this.isCaching) {
                return;
            }
            if (this.cropImage) {
                this.cropImage.src = readerEvent.target.result;
            }
            if (this.cropContainer) {
                this.cropContainer.classList.remove("d-none");
            }
            if (this.cropPreview) {
                this.cropPreview.classList.remove("d-none");
            }
            if (this.noPreview) {
                this.noPreview.classList.add("d-none");
            }
            if (this.cropControls) {
                this.cropControls.classList.remove("d-none");
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

            const response = await fetch("/admin/images/upload", {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers: {
                    "X-CSRF-Token": this.csrfTokenValue || "",
                },
            });

            if (!response.ok) {
                throw new Error("Upload failed");
            }

            const data = await response.json();
            if (!this.isActive || this.isCaching) {
                return;
            }

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
            if (!this.isActive || this.isCaching) {
                return;
            }
            console.error("Upload error:", error);
            alert("Upload failed: " + error.message);
        } finally {
            if (this.isActive && !this.isCaching && this.uploadBtn) {
                this.uploadBtn.disabled = false;
                this.uploadBtn.textContent = "Upload & Crop";
            }
        }
    }

    hideModalWithCleanup() {
        this.dismissBtn?.click();
        if (
            !window.bootstrap?.Modal ||
            !this.modal.classList.contains("show")
        ) {
            this.forceCloseModal();
        }
    }

    forceCloseModal() {
        if (this.modal) {
            this.modal.classList.remove("show");
            this.modal.style.display = "none";
            this.modal.setAttribute("aria-hidden", "true");
            this.modal.removeAttribute("aria-modal");
        }

        this.cleanupModalArtifacts();
    }

    cancelPendingRequests() {
        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = null;
        }
        this.loadRequest?.abort();
        this.loadRequest = null;
        this.searchRequest?.abort();
        this.searchRequest = null;
    }

    resetModalForCache() {
        this.isCaching = true;
        this.cancelPendingRequests();
        this.reader?.abort();
        this.reader = null;
        this.destroyCropper();
        this.forceCloseModal();
        this.onModalHidden();

        this.loadedImages = [];
        this.lastSearchQuery = "";
        this.lastLocalFiltered = [];
        if (this.searchInput) {
            this.searchInput.value = "";
        }
        this.gallery?.replaceChildren();
        if (this.uploadBtn) {
            this.uploadBtn.disabled = false;
            this.uploadBtn.textContent = "Upload & Crop";
        }
    }

    cleanupModalArtifacts() {
        const document = this.element.ownerDocument;
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
