import { Controller } from "@hotwired/stimulus";

const DEFAULT_CHUNK_SIZE = 3;

export default class extends Controller {
    static targets = [
        "form",
        "uploadsInput",
        "fileList",
        "uploadButton",
        "uploadStatus",
        "buttonLabel",
        "buttonSpinner",
    ];

    static values = {
        chunkSize: Number,
    };

    connect() {
        this.renderFileRows(this.currentFiles());
    }

    fileSelectionChanged(event) {
        this.renderFileRows(event.target.files || []);
    }

    async uploadAll(event) {
        event.preventDefault();

        const files = this.currentFiles();
        if (!files.length) {
            this.showStatus("danger", "Please choose at least one image file.");
            return;
        }

        this.setUploadingState(true);
        this.uploadStatusTarget.innerHTML = "";

        const allResults = [];
        try {
            for (let index = 0; index < files.length; index += this.chunkSize) {
                const chunk = files.slice(index, index + this.chunkSize);
                const chunkNumber = Math.floor(index / this.chunkSize) + 1;
                const data = await this.uploadChunk(chunk, chunkNumber);
                if (Array.isArray(data.results)) {
                    allResults.push(...data.results);
                } else if (data && data.error) {
                    allResults.push({
                        success: false,
                        name: `batch-${chunkNumber}`,
                        error: data.error,
                    });
                }
            }

            const successes = allResults.filter((result) => result && result.success);
            const failures = allResults.filter((result) => !result || !result.success);
            const details = this.buildDetails(allResults);

            if (successes.length && failures.length === 0) {
                this.showStatus("success", "All images uploaded successfully.", details);
            } else if (successes.length && failures.length) {
                this.showStatus("warning", "Some images uploaded; some failed.", details);
            } else {
                this.showStatus("danger", "Upload failed.", details);
            }
        } catch (error) {
            if (error && error.name === "NonJsonResponseError") {
                const status = Number(error.status || 0);
                if (status === 413) {
                    this.showStatus(
                        "danger",
                        "Upload request was too large for the server.",
                        '<div class="small">Try fewer images per batch or smaller files.</div>',
                    );
                } else {
                    this.showStatus(
                        "danger",
                        "Server returned invalid response (expected JSON, got HTML).",
                        `<div class="small">HTTP status: ${this.escapeHtml(String(status || "unknown"))}</div>`,
                    );
                }
            } else {
                this.showStatus(
                    "danger",
                    "Unexpected error while uploading.",
                    `<div class="small">${this.escapeHtml(error?.message || "Unknown error")}</div>`,
                );
            }
        } finally {
            this.setUploadingState(false);
        }
    }

    get chunkSize() {
        return this.hasChunkSizeValue && this.chunkSizeValue > 0
            ? this.chunkSizeValue
            : DEFAULT_CHUNK_SIZE;
    }

    currentFiles() {
        if (!this.hasUploadsInputTarget) {
            return [];
        }

        return Array.from(this.uploadsInputTarget.files || []);
    }

    renderFileRows(filesLike) {
        if (!this.hasFileListTarget || !this.hasUploadButtonTarget) {
            return;
        }

        const files = Array.from(filesLike || []);
        this.fileListTarget.innerHTML = "";

        if (!files.length) {
            this.uploadButtonTarget.setAttribute("disabled", "disabled");
            return;
        }

        this.uploadButtonTarget.removeAttribute("disabled");

        files.forEach((file, index) => {
            const col = document.createElement("div");
            col.className = "col-12";
            col.innerHTML =
                '<div class="border rounded p-3">' +
                '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">' +
                "<div>" +
                "<strong>" +
                this.escapeHtml(file.name) +
                "</strong>" +
                '<div class="text-muted small">' +
                Math.round(file.size / 1024) +
                " KB</div>" +
                "</div>" +
                `<span class="badge bg-secondary">#${index + 1}</span>` +
                "</div>" +
                '<div class="text-muted small">Tags will be applied from the "Entity Tags (Apply to All Files)" section above.</div>' +
                "</div>";
            this.fileListTarget.appendChild(col);
        });
    }

    setUploadingState(isUploading) {
        if (this.hasButtonLabelTarget) {
            this.buttonLabelTarget.classList.toggle("d-none", isUploading);
        }
        if (this.hasButtonSpinnerTarget) {
            this.buttonSpinnerTarget.classList.toggle("d-none", !isUploading);
        }

        if (!this.hasUploadButtonTarget) {
            return;
        }

        if (isUploading) {
            this.uploadButtonTarget.setAttribute("disabled", "disabled");
            return;
        }

        this.uploadButtonTarget.toggleAttribute("disabled", !this.currentFiles().length);
    }

    showStatus(type, message, detailsHtml = "") {
        if (!this.hasUploadStatusTarget) {
            return;
        }

        const alert = document.createElement("div");
        alert.className = `alert alert-${type}`;
        alert.innerHTML =
            `<div class="fw-semibold mb-1">${this.escapeHtml(message)}</div>` + detailsHtml;
        this.uploadStatusTarget.innerHTML = "";
        this.uploadStatusTarget.appendChild(alert);
    }

    buildDetails(results) {
        if (!results.length) {
            return "";
        }

        const items = results
            .map((result) => {
                const ok = Boolean(result && result.success);
                const statusClass = ok ? "text-success" : "text-danger";
                const icon = ok ? "bi-check-circle" : "bi-exclamation-circle";
                const fileName = result?.name ? this.escapeHtml(result.name) : "unnamed";
                const duplicate = result?.existing ? " (duplicate)" : "";
                const error = result?.error ? `: ${this.escapeHtml(result.error)}` : "";
                return `<li class="${statusClass}"><i class="bi ${icon}"></i> ${fileName}${duplicate}${error}</li>`;
            })
            .join("");

        return `<ul class="mb-0 ps-3">${items}</ul>`;
    }

    async uploadChunk(chunkFiles, chunkIndex) {
        const formData = new FormData(this.hasFormTarget ? this.formTarget : this.element);
        formData.delete("uploads[]");
        formData.delete("uploads");

        chunkFiles.forEach((file) => {
            formData.append("uploads[]", file, file.name);
        });

        const response = await fetch(
            this.element.getAttribute("action") || "/admin/images/bulk-upload",
            {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            },
        );

        const contentType = (response.headers.get("content-type") || "").toLowerCase();
        if (!contentType.includes("application/json")) {
            const text = await response.text();
            const err = new Error("non-json");
            err.name = "NonJsonResponseError";
            err.status = response.status;
            err.snippet = text.slice(0, 500);
            err.chunkIndex = chunkIndex;
            throw err;
        }

        return response.json();
    }

    escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }
}
