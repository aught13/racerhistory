import { Controller } from "@hotwired/stimulus";

const REDIRECT_DELAY_MS = 1500;

export default class extends Controller {
    static targets = [
        "fileInput",
        "previewContainer",
        "previewImage",
        "manipulationControls",
        "noFileText",
        "brightness",
        "brightnessBadge",
        "contrast",
        "contrastBadge",
        "rotate",
        "form",
        "submitLabel",
        "submitSpinner",
        "status",
        "tags",
    ];

    connect() {
        this.currentImageFile = null;
        this.boundHandleFileChange = (event) => this.handleFileChange(event);
        this.boundHandleBrightnessInput = (event) =>
            this.handleBrightnessInput(event);
        this.boundHandleContrastInput = (event) =>
            this.handleContrastInput(event);
        this.boundSubmit = (event) => this.submit(event);

        if (this.hasFileInputTarget) {
            this.fileInputTarget.addEventListener(
                "change",
                this.boundHandleFileChange,
            );
        }
        if (this.hasBrightnessTarget) {
            this.brightnessTarget.addEventListener(
                "input",
                this.boundHandleBrightnessInput,
            );
        }
        if (this.hasContrastTarget) {
            this.contrastTarget.addEventListener(
                "input",
                this.boundHandleContrastInput,
            );
        }
        if (this.hasFormTarget) {
            this.formTarget.addEventListener("submit", this.boundSubmit);
        }

        this.updateAdjustmentBadge(this.brightnessBadgeTarget, 0);
        this.updateAdjustmentBadge(this.contrastBadgeTarget, 0);
        this.showEmptyPreviewState();
    }

    disconnect() {
        if (this.hasFileInputTarget) {
            this.fileInputTarget.removeEventListener(
                "change",
                this.boundHandleFileChange,
            );
        }
        if (this.hasBrightnessTarget) {
            this.brightnessTarget.removeEventListener(
                "input",
                this.boundHandleBrightnessInput,
            );
        }
        if (this.hasContrastTarget) {
            this.contrastTarget.removeEventListener(
                "input",
                this.boundHandleContrastInput,
            );
        }
        if (this.hasFormTarget) {
            this.formTarget.removeEventListener("submit", this.boundSubmit);
        }
    }

    handleFileChange(event) {
        const file = event.target.files?.[0];
        if (!file) {
            this.currentImageFile = null;
            this.showEmptyPreviewState();
            return;
        }

        this.currentImageFile = file;
        const reader = new FileReader();
        reader.onload = (readerEvent) => {
            if (this.hasPreviewImageTarget) {
                this.previewImageTarget.src = String(
                    readerEvent.target?.result || "",
                );
            }
            this.showPreviewState();
        };
        reader.readAsDataURL(file);
    }

    handleBrightnessInput(event) {
        const value = event.target?.value ?? event.currentTarget?.value ?? 0;
        this.updateAdjustmentBadge(
            this.brightnessBadgeTarget,
            value,
        );
    }

    handleContrastInput(event) {
        const value = event.target?.value ?? event.currentTarget?.value ?? 0;
        this.updateAdjustmentBadge(
            this.contrastBadgeTarget,
            value,
        );
    }

    setRotation(event) {
        if (!this.hasRotateTarget) {
            return;
        }

        const degrees = Number(event.params?.degrees ?? 0);
        this.rotateTarget.value = String(degrees);
    }

    async submit(event) {
        event.preventDefault();

        if (!this.currentImageFile) {
            this.showStatus("error", "Please select an image file");
            return;
        }

        const formData = new FormData();
        formData.append("upload", this.currentImageFile);

        if (this.hasTagsTarget) {
            const tags = this.tagsTarget.value.trim();
            if (tags) {
                formData.append("tags", tags);
            }
        }

        const rotate = parseInt(
            this.hasRotateTarget ? this.rotateTarget.value : "0",
            10,
        );
        if (Number.isFinite(rotate) && rotate > 0) {
            formData.append("rotate", String(rotate));
        }

        const brightness = parseInt(
            this.hasBrightnessTarget ? this.brightnessTarget.value : "0",
            10,
        );
        if (Number.isFinite(brightness) && brightness !== 0) {
            formData.append("brightness", String(brightness));
        }

        const contrast = parseInt(
            this.hasContrastTarget ? this.contrastTarget.value : "0",
            10,
        );
        if (Number.isFinite(contrast) && contrast !== 0) {
            formData.append("contrast", String(contrast));
        }

        this.setSubmitting(true);

        try {
            const response = await fetch(
                this.hasFormTarget && this.formTarget.getAttribute("action")
                    ? this.formTarget.getAttribute("action")
                    : "/admin/images/upload",
                {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                },
            );

            const data = await response.json();

            if (data.success) {
                this.showStatus(
                    "success",
                    "Image uploaded successfully! Redirecting...",
                );
                window.setTimeout(() => {
                    window.location.assign(`/admin/images/edit/${data.image.id}`);
                }, REDIRECT_DELAY_MS);
                return;
            }

            this.showStatus("error", data.error || "Upload failed");
            this.setSubmitting(false);
        } catch (error) {
            this.showStatus("error", `Upload error: ${error.message}`);
            this.setSubmitting(false);
        }
    }

    showEmptyPreviewState() {
        if (this.hasPreviewImageTarget) {
            this.previewImageTarget.removeAttribute("src");
        }

        this.togglePreviewSections(false);
    }

    showPreviewState() {
        this.togglePreviewSections(true);
    }

    togglePreviewSections(showPreview) {
        if (this.hasPreviewContainerTarget) {
            this.previewContainerTarget.classList.toggle("d-none", !showPreview);
        }
        if (this.hasManipulationControlsTarget) {
            this.manipulationControlsTarget.classList.toggle(
                "d-none",
                !showPreview,
            );
        }
        if (this.hasNoFileTextTarget) {
            this.noFileTextTarget.classList.toggle("d-none", showPreview);
        }
    }

    updateAdjustmentBadge(badgeElement, value) {
        if (!badgeElement) {
            return;
        }

        const numericValue = Number(value || 0);
        badgeElement.textContent = String(numericValue);
        badgeElement.className = `badge ${
            numericValue > 0
                ? "bg-success"
                : numericValue < 0
                    ? "bg-danger"
                    : "bg-secondary"
        }`;
    }

    setSubmitting(isSubmitting) {
        if (this.hasSubmitLabelTarget) {
            this.submitLabelTarget.classList.toggle("d-none", isSubmitting);
        }
        if (this.hasSubmitSpinnerTarget) {
            this.submitSpinnerTarget.classList.toggle("d-none", !isSubmitting);
        }
        if (this.hasFormTarget) {
            const submitButton = this.formTarget.querySelector(
                'button[type="submit"]',
            );
            if (submitButton) {
                submitButton.disabled = isSubmitting;
            }
        }
    }

    showStatus(type, message) {
        if (!this.hasStatusTarget) {
            return;
        }

        const alertClass = type === "success" ? "alert-success" : "alert-danger";
        this.statusTarget.innerHTML = `<div class="alert ${alertClass}" role="alert">${message}</div>`;
        this.statusTarget.classList.remove("d-none");
    }
}
