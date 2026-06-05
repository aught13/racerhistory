import { Controller } from "@hotwired/stimulus";

const TINYMCE_RETRY_DELAY_MS = 200;
const TINYMCE_MAX_RETRIES = 20;

export default class extends Controller {
    static targets = [
        "editor",
        "heroField",
        "heroPreview",
        "heroVariantButton",
        "unsetHeroButton",
        "inlineField",
    ];

    static values = {
        existingHeroId: Number,
        existingHeroUrl: String,
        imagesUploadUrl: {
            type: String,
            default: "/admin/images/upload",
        },
    };

    connect() {
        this.tinyMceRetryCount = 0;
        this.tinyMceRetryTimer = null;
        this.boundBeforeRender = () => this.destroyTinyMCE();
        this.boundBeforeCache = () => this.destroyTinyMCE();
        this.boundHeroChange = () => this.onHeroFieldChange();
        this.boundUnsetHero = () => this.clearHeroImage();
        this.boundInlineChange = () => this.onInlineFieldChange();

        document.addEventListener(
            "turbo:before-render",
            this.boundBeforeRender,
        );
        document.addEventListener("turbo:before-cache", this.boundBeforeCache);

        if (this.hasHeroFieldTarget) {
            this.heroFieldTarget.addEventListener(
                "change",
                this.boundHeroChange,
            );
            if (this.existingHeroIdValue > 0 && !this.heroFieldTarget.value) {
                this.heroFieldTarget.value = String(this.existingHeroIdValue);
            }
        }
        if (this.hasUnsetHeroButtonTarget) {
            this.unsetHeroButtonTarget.addEventListener(
                "click",
                this.boundUnsetHero,
            );
        }
        if (this.hasInlineFieldTarget) {
            this.inlineFieldTarget.addEventListener(
                "change",
                this.boundInlineChange,
            );
        }

        this.updateHeroImageState();
        this.initTinyMceWhenReady();
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-render",
            this.boundBeforeRender,
        );
        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );

        if (this.hasHeroFieldTarget) {
            this.heroFieldTarget.removeEventListener(
                "change",
                this.boundHeroChange,
            );
        }
        if (this.hasUnsetHeroButtonTarget) {
            this.unsetHeroButtonTarget.removeEventListener(
                "click",
                this.boundUnsetHero,
            );
        }
        if (this.hasInlineFieldTarget) {
            this.inlineFieldTarget.removeEventListener(
                "change",
                this.boundInlineChange,
            );
        }

        if (this.tinyMceRetryTimer) {
            window.clearTimeout(this.tinyMceRetryTimer);
            this.tinyMceRetryTimer = null;
        }

        this.destroyTinyMCE();
    }

    initTinyMceWhenReady() {
        if (!this.hasEditorTarget) {
            return;
        }

        const editorId = this.editorTarget.id;
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
            menubar: true,
            menu: {
                file: { title: "File", items: "preview | print" },
                edit: {
                    title: "Edit",
                    items: "undo redo | cut copy paste | selectall | searchreplace",
                },
                view: {
                    title: "View",
                    items: "visualblocks visualchars | fullscreen",
                },
                insert: {
                    title: "Insert",
                    items: "image media table link | hr | charmap",
                },
                format: {
                    title: "Format",
                    items: "bold italic underline strikethrough | formats blockformats fontformats fontsizes align | forecolor backcolor | removeformat",
                },
                table: {
                    title: "Table",
                    items: "inserttable | cell row column | tableprops deletetable",
                },
                help: { title: "Help", items: "help" },
            },
            min_height: 500,
            resize: true,
            statusbar: true,
            branding: false,
            plugins:
                "image code lists advlist media preview quickbars save visualblocks visualchars table link autolink searchreplace fullscreen wordcount help",
            toolbar: [
                "undo redo | blocks styles | bold italic underline strikethrough | forecolor backcolor",
                "alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | blockquote",
                "link image media table | removeformat visualblocks | code fullscreen preview | help",
            ].join(" | "),
            quickbars_selection_toolbar:
                "bold italic underline | quicklink blockquote | bullist numlist",
            quickbars_insert_toolbar: "quickimage quicktable hr",
            block_formats:
                "Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Blockquote=blockquote; Preformatted=pre",
            style_formats: [
                {
                    title: "Text Styles",
                    items: [
                        {
                            title: "Lead Paragraph",
                            selector: "p",
                            classes: "lead",
                        },
                        { title: "Small Text", inline: "small" },
                        {
                            title: "Muted Text",
                            selector: "p,span",
                            classes: "text-muted",
                        },
                    ],
                },
                {
                    title: "Image Position",
                    items: [
                        {
                            title: "Float Left",
                            selector: "img,figure,picture",
                            classes: "img-float-left",
                            styles: {
                                float: "left",
                                margin: "0.5rem 1.5rem 1rem 0",
                            },
                        },
                        {
                            title: "Float Right",
                            selector: "img,figure,picture",
                            classes: "img-float-right",
                            styles: {
                                float: "right",
                                margin: "0.5rem 0 1rem 1.5rem",
                            },
                        },
                        {
                            title: "Center",
                            selector: "img,figure,picture",
                            classes: "img-center",
                            styles: { display: "block", margin: "1rem auto" },
                        },
                    ],
                },
            ],
            content_css: "/css/blog-content.css",
            content_style:
                'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 1.125rem; line-height: 1.8; padding: 1rem; max-width: 100%; } img { max-width: 100%; height: auto; border-radius: 6px; }',
            image_title: true,
            automatic_uploads: true,
            images_upload_url: uploadUrl,
            images_upload_credentials: true,
            images_reuse_filename: true,
            convert_urls: false,
            relative_urls: false,
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
            table_default_styles: { width: "100%" },
            table_class_list: [
                { title: "Default", value: "" },
                { title: "Striped", value: "table table-striped" },
                { title: "Bordered", value: "table table-bordered" },
                { title: "Hover", value: "table table-hover" },
                { title: "Responsive", value: "table-responsive" },
            ],
            link_assume_external_targets: true,
            link_default_target: "_blank",
            extended_valid_elements:
                "img[class|src|srcset|sizes|alt|title|width|height|loading|style|data-*],picture[class|style],source[srcset|sizes|type|media],figure[class|style],figcaption[class|style],iframe[src|width|height|frameborder|allowfullscreen|class|style|title],video[src|controls|autoplay|loop|muted|poster|class|style|width|height]",
        });
    }

    destroyTinyMCE() {
        if (typeof window.tinymce !== "undefined") {
            const editor = window.tinymce.get("body-editor");
            if (editor) {
                editor.remove();
            }
        }
    }

    onHeroFieldChange() {
        this.updateHeroImageState();
    }

    clearHeroImage() {
        if (this.hasHeroFieldTarget) {
            this.heroFieldTarget.value = "";
            this.heroFieldTarget.dispatchEvent(
                new Event("change", { bubbles: true }),
            );
        }
    }

    updateHeroImageState() {
        if (!this.hasHeroFieldTarget) {
            return;
        }

        const heroValue = this.heroFieldTarget.value.trim();
        const imageId = Number.parseInt(heroValue, 10);
        const selectedUrl =
            this.heroFieldTarget.dataset.selectedImageHeroUrl ||
            this.heroFieldTarget.dataset.selectedImageThumbnailUrl ||
            this.heroFieldTarget.dataset.selectedImageUrl ||
            "";
        const previewUrl =
            selectedUrl ||
            (Number.isFinite(imageId) && imageId === this.existingHeroIdValue
                ? this.existingHeroUrlValue
                : "");

        if (Number.isFinite(imageId) && imageId > 0 && previewUrl !== "") {
            if (this.hasHeroPreviewTarget) {
                const img = this.heroPreviewTarget.querySelector("img");
                if (img) {
                    img.src = this.withCacheBust(previewUrl);
                }
                this.heroPreviewTarget.style.display = "block";
            }
            if (this.hasUnsetHeroButtonTarget) {
                this.unsetHeroButtonTarget.style.display = "inline-block";
            }
        } else {
            if (this.hasHeroPreviewTarget) {
                this.heroPreviewTarget.style.display = "none";
            }
            if (this.hasUnsetHeroButtonTarget) {
                this.unsetHeroButtonTarget.style.display = "none";
            }
        }

        if (this.hasHeroVariantButtonTarget) {
            if (Number.isFinite(imageId) && imageId > 0) {
                this.heroVariantButtonTarget.href = `/admin/images/crop-hero/${imageId}`;
                this.heroVariantButtonTarget.style.display = "block";
            } else {
                this.heroVariantButtonTarget.style.display = "none";
            }
        }
    }

    onInlineFieldChange() {
        const inlineValue = this.hasInlineFieldTarget
            ? this.inlineFieldTarget.value.trim()
            : "";
        const imageId = Number.parseInt(inlineValue, 10);
        if (!Number.isFinite(imageId) || imageId <= 0) {
            return;
        }

        const imageUrl = this.inlineFieldTarget.dataset.selectedImageUrl || "";
        if (!imageUrl) {
            return;
        }

        const editor = window.tinymce?.activeEditor;
        if (!editor) {
            return;
        }

        editor.insertContent(
            `<picture><img src="${imageUrl}" alt="" class="img-fluid" loading="lazy"></picture><p></p>`,
        );
        this.inlineFieldTarget.value = "";
        delete this.inlineFieldTarget.dataset.selectedImageUrl;
        delete this.inlineFieldTarget.dataset.selectedImageThumbnailUrl;
    }

    withCacheBust(url) {
        if (!url) {
            return "";
        }

        return url + (url.includes("?") ? "&" : "?") + "_ts=" + Date.now();
    }
}
