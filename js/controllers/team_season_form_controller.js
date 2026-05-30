import { Controller } from "@hotwired/stimulus";

const TINYMCE_RETRY_DELAY_MS = 200;
const TINYMCE_MAX_RETRIES = 20;

export default class extends Controller {
    static targets = [
        "previewEditor",
        "recapEditor",
        "imageField",
        "imagePreview",
        "heroVariantButton",
        "uploadButton",
    ];

    static values = {
        existingImageId: String,
        existingPreviewUrl: String,
        uploadUrl: {
            type: String,
            default: "/admin/images/upload",
        },
    };

    connect() {
        this.tinyMceRetryCount = 0;
        this.tinyMceRetryTimer = null;
        this.boundBeforeCache = () => this.destroyTinyMCE();
        this.boundBeforeRender = () => this.destroyTinyMCE();
        this.boundImageChange = () => this.updateImagePreview();
        this.boundUploadClick = (event) => this.handleUploadClick(event);

        document.addEventListener("turbo:before-cache", this.boundBeforeCache);
        document.addEventListener(
            "turbo:before-render",
            this.boundBeforeRender,
        );

        if (this.hasImageFieldTarget) {
            this.imageFieldTarget.addEventListener(
                "change",
                this.boundImageChange,
            );
            if (this.existingImageIdValue && !this.imageFieldTarget.value) {
                this.imageFieldTarget.value = this.existingImageIdValue;
            }
        }

        if (this.hasUploadButtonTarget) {
            this.uploadButtonTarget.addEventListener(
                "click",
                this.boundUploadClick,
            );
        }

        this.updateImagePreview();
        this.updateHeroVariantButton();
        this.initTinyMceWhenReady();
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        document.removeEventListener(
            "turbo:before-render",
            this.boundBeforeRender,
        );

        if (this.hasImageFieldTarget) {
            this.imageFieldTarget.removeEventListener(
                "change",
                this.boundImageChange,
            );
        }

        if (this.hasUploadButtonTarget) {
            this.uploadButtonTarget.removeEventListener(
                "click",
                this.boundUploadClick,
            );
        }

        if (this.tinyMceRetryTimer) {
            window.clearTimeout(this.tinyMceRetryTimer);
            this.tinyMceRetryTimer = null;
        }

        this.destroyTinyMCE();
    }

    initTinyMceWhenReady() {
        const editorIds = [];
        if (this.hasPreviewEditorTarget) {
            editorIds.push(this.previewEditorTarget.id);
        }
        if (this.hasRecapEditorTarget) {
            editorIds.push(this.recapEditorTarget.id);
        }

        if (editorIds.length === 0) {
            return;
        }

        if (typeof window.tinymce === "undefined") {
            if (this.tinyMceRetryCount >= TINYMCE_MAX_RETRIES) {
                return;
            }

            this.tinyMceRetryCount += 1;
            this.tinyMceRetryTimer = window.setTimeout(() => {
                this.initTinyMceWhenReady();
            }, TINYMCE_RETRY_DELAY_MS);
            return;
        }

        editorIds.forEach((editorId) => {
            if (!editorId || window.tinymce.get(editorId)) {
                return;
            }

            window.tinymce.init({
                license_key: "gpl",
                selector: `#${editorId}`,
                menubar: false,
                plugins:
                    "image code lists advlist media preview quickbars save visualblocks visualchars",
                toolbar:
                    "undo redo | blocks | bold italic underline | bullist numlist | image media | code preview",
                quickbars_selection_toolbar:
                    "bold italic underline | quicklink blockquote | bullist numlist",
                image_title: true,
                automatic_uploads: true,
                images_upload_url: this.uploadUrlValue,
                images_upload_credentials: true,
                convert_urls: false,
                images_upload_handler: (blobInfo, progress) => {
                    return new Promise((resolve, reject) => {
                        const xhr = new window.XMLHttpRequest();
                        xhr.open("POST", this.uploadUrlValue);
                        xhr.withCredentials = true;
                        xhr.upload.onprogress = (event) => {
                            if (event.lengthComputable) {
                                progress((event.loaded / event.total) * 100);
                            }
                        };
                        xhr.onload = () => {
                            if (xhr.status < 200 || xhr.status >= 300) {
                                reject("HTTP Error: " + xhr.status);
                                return;
                            }

                            let json;
                            try {
                                json = JSON.parse(xhr.responseText);
                            } catch {
                                reject("Invalid JSON");
                                return;
                            }

                            if (
                                !json.success ||
                                !json.image ||
                                !json.image.url
                            ) {
                                reject(json.error || "Upload failed");
                                return;
                            }

                            resolve(json.image.url);
                        };
                        xhr.onerror = () => reject("Image upload failed");

                        const formData = new FormData();
                        formData.append(
                            "upload",
                            blobInfo.blob(),
                            blobInfo.filename(),
                        );
                        const csrf = document.querySelector(
                            'meta[name="csrfToken"]',
                        );
                        if (csrf) {
                            xhr.setRequestHeader(
                                "X-CSRF-Token",
                                csrf.getAttribute("content"),
                            );
                        }
                        xhr.send(formData);
                    });
                },
            });
        });
    }

    destroyTinyMCE() {
        if (typeof window.tinymce === "undefined") {
            return;
        }

        [
            this.hasPreviewEditorTarget ? this.previewEditorTarget.id : null,
            this.hasRecapEditorTarget ? this.recapEditorTarget.id : null,
        ]
            .filter(Boolean)
            .forEach((editorId) => {
                const editor = window.tinymce.get(editorId);
                if (editor) {
                    editor.remove();
                }
            });
    }

    handleUploadClick(event) {
        event.preventDefault();

        const input = document.createElement("input");
        input.type = "file";
        input.accept = "image/*";
        input.addEventListener("change", () => this.uploadSelectedFile(input));
        input.click();
    }

    async uploadSelectedFile(fileInput) {
        if (
            !fileInput.files ||
            !fileInput.files[0] ||
            !this.hasImageFieldTarget
        ) {
            return;
        }

        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append("upload", file);

        if (this.hasUploadButtonTarget) {
            this.uploadButtonTarget.disabled = true;
            this.uploadButtonTarget.textContent = "Uploading...";
        }

        try {
            const response = await fetch(this.uploadUrlValue, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers: {
                    "X-CSRF-Token":
                        document
                            .querySelector('meta[name="csrfToken"]')
                            ?.getAttribute("content") || "",
                },
            });

            if (!response.ok) {
                throw new Error("Upload failed");
            }

            const data = await response.json();
            if (!data.success || !data.image) {
                throw new Error(data.error || "Upload failed");
            }

            this.imageFieldTarget.value = String(data.image.id);
            this.imageFieldTarget.dataset.selectedImageUrl =
                data.image.url || "";
            this.imageFieldTarget.dataset.selectedImageThumbnailUrl =
                data.image.thumbnail_url || data.image.url || "";
            this.imageFieldTarget.dataset.selectedImageHeroUrl =
                data.image.hero_url || data.image.url || "";
            this.updateImagePreview();
            this.updateHeroVariantButton();
            this.imageFieldTarget.dispatchEvent(
                new Event("change", { bubbles: true }),
            );
        } catch (error) {
            console.error("Team season image upload failed:", error);
            alert(`Upload failed: ${error.message}`);
        } finally {
            if (this.hasUploadButtonTarget) {
                this.uploadButtonTarget.disabled = false;
                this.uploadButtonTarget.textContent = "Select / Upload";
            }
        }
    }

    updateImagePreview() {
        if (!this.hasImageFieldTarget || !this.hasImagePreviewTarget) {
            return;
        }

        const imageId = this.imageFieldTarget.value.trim();
        const selectedUrl =
            this.imageFieldTarget.dataset.selectedImageHeroUrl ||
            this.imageFieldTarget.dataset.selectedImageThumbnailUrl ||
            this.imageFieldTarget.dataset.selectedImageUrl ||
            "";
        const previewUrl =
            selectedUrl ||
            (imageId === this.existingImageIdValue
                ? this.existingPreviewUrlValue
                : "");

        if (
            imageId &&
            !Number.isNaN(Number.parseInt(imageId, 10)) &&
            previewUrl
        ) {
            const previewImg = this.imagePreviewTarget.querySelector("img");
            if (previewImg) {
                previewImg.src = this.withCacheBust(previewUrl);
            }
            this.imagePreviewTarget.style.display = "block";
        } else {
            this.imagePreviewTarget.style.display = "none";
        }
    }

    updateHeroVariantButton() {
        if (!this.hasHeroVariantButtonTarget || !this.hasImageFieldTarget) {
            return;
        }

        const imageId = Number.parseInt(this.imageFieldTarget.value.trim(), 10);
        if (Number.isFinite(imageId) && imageId > 0) {
            this.heroVariantButtonTarget.href = `/admin/images/crop-hero/${imageId}`;
            this.heroVariantButtonTarget.style.display = "block";
        } else {
            this.heroVariantButtonTarget.style.display = "none";
        }
    }

    withCacheBust(url) {
        if (!url) {
            return "";
        }

        return url + (url.includes("?") ? "&" : "?") + "_ts=" + Date.now();
    }
}
