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
        "teamSelect",
        "seasonSelect",
    ];

    static values = {
        existingImageId: String,
        existingPreviewUrl: String,
        csrfToken: String,
        maxUploadBytes: {
            type: Number,
            default: 0,
        },
        uploadUrl: {
            type: String,
            default: "/admin/images/upload",
        },
    };

    initialize() {
        this.uploadRequest = null;
        this.isCaching = false;
    }

    connect() {
        this.tinyMceRetryCount = 0;
        this.tinyMceRetryTimer = null;
        this.isCaching = false;

        if (this.hasImageFieldTarget) {
            if (this.existingImageIdValue && !this.imageFieldTarget.value) {
                this.imageFieldTarget.value = this.existingImageIdValue;
            }
        }

        this.updateImagePreview();
        this.updateHeroVariantButton();
        this.initTinyMceWhenReady();
    }

    disconnect() {
        this.prepareForNavigation();
    }

    onTurboLoad() {
        this.isCaching = false;
        this.tinyMceRetryCount = 0;
        this.initTinyMceWhenReady();
    }

    beforeCache() {
        this.prepareForNavigation();
    }

    beforeRender() {
        this.prepareForNavigation();
    }

    prepareForNavigation() {
        this.isCaching = true;
        if (this.tinyMceRetryTimer !== null) {
            window.clearTimeout(this.tinyMceRetryTimer);
            this.tinyMceRetryTimer = null;
        }
        if (this.uploadRequest) {
            this.uploadRequest.abort();
            this.uploadRequest = null;
        }
        this.restoreUploadButton();
        this.destroyTinyMCE();
    }

    restoreUploadButton() {
        if (!this.hasUploadButtonTarget) {
            return;
        }

        this.uploadButtonTarget.disabled = false;
        this.uploadButtonTarget.textContent = "Select / Upload";
    }

    handleImageFieldChange() {
        this.updateImagePreview();
        this.updateHeroVariantButton();
    }

    popupOptionAdded(event) {
        const detail = event.detail;
        if (!detail?.targetSelectId || detail.newOption?.value == null) {
            return;
        }

        const select = [
            this.hasTeamSelectTarget ? this.teamSelectTarget : null,
            this.hasSeasonSelectTarget ? this.seasonSelectTarget : null,
        ].find((target) => target?.id === detail.targetSelectId);
        if (!select) {
            return;
        }

        const value = String(detail.newOption.value);
        let option = Array.from(select.options).find(
            (candidate) => candidate.value === value,
        );
        if (!option) {
            option = select.ownerDocument.createElement("option");
            option.value = value;
            select.add(option);
        }
        option.textContent = String(detail.newOption.text ?? value);
        select.value = value;
        select.dispatchEvent(new Event("change", { bubbles: true }));
    }

    initTinyMceWhenReady() {
        if (this.isCaching) {
            return;
        }

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
            if (this.tinyMceRetryTimer !== null) {
                return;
            }
            if (this.tinyMceRetryCount >= TINYMCE_MAX_RETRIES) {
                return;
            }

            this.tinyMceRetryCount += 1;
            this.tinyMceRetryTimer = window.setTimeout(() => {
                this.tinyMceRetryTimer = null;
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
                        const blob = blobInfo.blob();
                        if (
                            this.maxUploadBytesValue > 0 &&
                            blob.size > this.maxUploadBytesValue
                        ) {
                            reject(
                                "File too large. Maximum allowed: " +
                                    (
                                        this.maxUploadBytesValue /
                                        1024 /
                                        1024
                                    ).toFixed(1) +
                                    "MB",
                            );
                            return;
                        }

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

                        const csrfToken = this.resolveCsrfToken();
                        if (csrfToken) {
                            formData.append("_csrfToken", csrfToken);
                            xhr.setRequestHeader("X-CSRF-Token", csrfToken);
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

        try {
            [
                this.hasPreviewEditorTarget ? this.previewEditorTarget : null,
                this.hasRecapEditorTarget ? this.recapEditorTarget : null,
            ]
                .filter(Boolean)
                .forEach((target) => {
                    const editor = window.tinymce.get(target.id);
                    if (
                        editor &&
                        typeof editor === "object" &&
                        typeof editor.getContent === "function"
                    ) {
                        target.value = editor.getContent();
                    }

                    if (
                        editor &&
                        typeof editor === "object" &&
                        typeof editor.remove === "function"
                    ) {
                        editor.remove();
                    } else {
                        window.tinymce.remove(`#${target.id}`);
                    }
                });
        } catch (e) {
            console.warn("Error destroying TinyMCE editors:", e);
        }
    }

    handleUploadClick(event) {
        event.preventDefault();

        const input = document.createElement("input");
        input.type = "file";
        input.accept = "image/*";
        input.addEventListener("change", () => this.uploadSelectedFile(input), {
            once: true,
        });
        input.click();
    }

    async uploadSelectedFile(fileInput) {
        if (
            !fileInput.files ||
            !fileInput.files[0] ||
            !this.hasImageFieldTarget ||
            this.isCaching
        ) {
            return;
        }

        const file = fileInput.files[0];
        if (
            this.maxUploadBytesValue > 0 &&
            file.size > this.maxUploadBytesValue
        ) {
            alert(
                "File too large. Maximum allowed: " +
                    (this.maxUploadBytesValue / 1024 / 1024).toFixed(1) +
                    "MB",
            );
            return;
        }
        const formData = new FormData();
        formData.append("upload", file);
        const csrfToken = this.resolveCsrfToken();
        if (csrfToken) {
            formData.append("_csrfToken", csrfToken);
        }

        if (this.hasUploadButtonTarget) {
            this.uploadButtonTarget.disabled = true;
            this.uploadButtonTarget.textContent = "Uploading...";
        }

        this.uploadRequest?.abort();
        const request = new AbortController();
        this.uploadRequest = request;

        try {
            const headers = {};
            if (csrfToken) {
                headers["X-CSRF-Token"] = csrfToken;
            }

            const response = await fetch(this.uploadUrlValue, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers,
                signal: request.signal,
            });

            if (
                this.uploadRequest !== request ||
                this.isCaching ||
                !this.element.isConnected
            ) {
                return;
            }

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
            if (
                error?.name !== "AbortError" &&
                this.uploadRequest === request &&
                !this.isCaching &&
                this.element.isConnected
            ) {
                console.error("Team season image upload failed:", error);
                alert(`Upload failed: ${error.message}`);
            }
        } finally {
            if (this.uploadRequest === request) {
                this.uploadRequest = null;
                this.restoreUploadButton();
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

    resolveCsrfToken() {
        const scopedFormToken = this.element
            ?.querySelector('input[name="_csrfToken"]')
            ?.value?.trim();
        if (scopedFormToken) {
            return scopedFormToken;
        }

        const configuredToken = this.csrfTokenValue?.trim();
        if (configuredToken) {
            return configuredToken;
        }

        return this.readCookie("csrfToken") || this.readCookie("_csrfToken");
    }

    readCookie(name) {
        const needle = `${name}=`;
        const match = document.cookie
            .split(";")
            .map((part) => part.trim())
            .find((part) => part.startsWith(needle));

        if (!match) {
            return "";
        }

        return decodeURIComponent(match.slice(needle.length));
    }
}
