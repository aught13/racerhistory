import { Controller } from "@hotwired/stimulus";

const TINYMCE_RETRY_DELAY_MS = 200;
const TINYMCE_MAX_RETRIES = 20;

export default class extends Controller {
    static targets = ["bioEditor", "imageField", "imagePreview"];

    static values = {
        initialImageId: String,
        initialPreviewUrl: String,
        imagesUploadUrl: {
            type: String,
            default: "/admin/images/upload",
        },
    };

    connect() {
        this.tinyMceRetryCount = 0;
        this.tinyMceRetryTimer = null;

        this.boundBeforeCache = () => this.removeTinyMceEditor();
        this.boundTurboLoad = () => this.onTurboLoad();
        this.boundImageChange = () => this.updateImagePreview();

        document.addEventListener("turbo:before-cache", this.boundBeforeCache);
        document.addEventListener("turbo:load", this.boundTurboLoad);

        if (this.hasImageFieldTarget) {
            this.imageFieldTarget.addEventListener(
                "change",
                this.boundImageChange,
            );
            this.updateImagePreview();
        }

        this.initTinyMceWhenReady();
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        document.removeEventListener("turbo:load", this.boundTurboLoad);

        if (this.hasImageFieldTarget) {
            this.imageFieldTarget.removeEventListener(
                "change",
                this.boundImageChange,
            );
        }

        if (this.tinyMceRetryTimer) {
            window.clearTimeout(this.tinyMceRetryTimer);
            this.tinyMceRetryTimer = null;
        }

        this.removeTinyMceEditor();
    }

    onTurboLoad() {
        // Re-initialize TinyMCE after successful Turbo navigation
        this.tinyMceRetryCount = 0;
        this.initTinyMceWhenReady();
    }

    initTinyMceWhenReady() {
        if (!this.hasBioEditorTarget) {
            return;
        }

        const editorId = this.bioEditorTarget.id;
        if (!editorId) {
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

        if (window.tinymce.get(editorId)) {
            return;
        }

        const uploadUrl = this.imagesUploadUrlValue;
        window.tinymce.init({
            license_key: "gpl",
            selector: `#${editorId}`,
            menubar: false,
            plugins:
                "image code lists advlist media preview quickbars save visualblocks visualchars",
            toolbar:
                "undo redo | blocks | bold italic underline | bullist numlist | image media | code preview | save",
            quickbars_selection_toolbar:
                "bold italic underline | quicklink blockquote | bullist numlist",
            image_title: true,
            automatic_uploads: true,
            images_upload_url: uploadUrl,
            images_upload_credentials: true,
            convert_urls: false,
            images_upload_handler: (blobInfo, progress) => {
                return new Promise((resolve, reject) => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.open("POST", uploadUrl);
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
                            console.error(
                                "TinyMCE upload invalid JSON response:",
                                xhr.responseText,
                            );
                            reject("Invalid JSON");
                            return;
                        }

                        if (!json.success || !json.image || !json.image.url) {
                            console.error(
                                "TinyMCE upload server response (error path):",
                                json,
                            );
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
    }

    removeTinyMceEditor() {
        if (!this.hasBioEditorTarget || typeof window.tinymce === "undefined") {
            return;
        }

        try {
            const editorId = this.bioEditorTarget.id;
            if (editorId) {
                const editor = window.tinymce.get(editorId);
                // Save content before removing to prevent data loss
                if (
                    editor &&
                    typeof editor === "object" &&
                    typeof editor.getContent === "function"
                ) {
                    this.bioEditorTarget.value = editor.getContent();
                }
                // Remove the editor instance (use selector string or editor object)
                window.tinymce.remove(editor || `#${editorId}`);
            }
        } catch (e) {
            console.warn("Error removing TinyMCE editor:", e);
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
