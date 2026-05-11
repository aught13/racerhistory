(function () {
    "use strict";

    const BULK_CHUNK_SIZE = 3;
    const state = {
        cleanups: {},
        cropSelectorPromise: null,
    };

    function replaceCleanup(key, cleanup) {
        if (typeof state.cleanups[key] === "function") {
            try {
                state.cleanups[key]();
            } catch (err) {
                console.debug("cleanup error", err);
            }
        }
        state.cleanups[key] = cleanup;
    }

    function cleanupAll() {
        Object.keys(state.cleanups).forEach((key) => {
            if (typeof state.cleanups[key] === "function") {
                try {
                    state.cleanups[key]();
                } catch (err) {
                    console.debug("cleanup error", err);
                }
            }
            delete state.cleanups[key];
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function initBulkUploadPage() {
        const formElement = document.getElementById("bulkUploadForm");
        const uploadsInput = document.getElementById("uploads");
        const fileList = document.getElementById("fileList");
        const uploadBtn = document.getElementById("uploadAll");
        const statusBox = document.getElementById("uploadStatus");

        if (
            !formElement ||
            !uploadsInput ||
            !fileList ||
            !uploadBtn ||
            !statusBox
        ) {
            return;
        }
        if (formElement.dataset.bulkUploadBound === "1") {
            return;
        }
        formElement.dataset.bulkUploadBound = "1";

        function renderFileRows(files) {
            fileList.innerHTML = "";
            if (!files.length) {
                uploadBtn.setAttribute("disabled", "disabled");
                return;
            }
            uploadBtn.removeAttribute("disabled");

            Array.from(files).forEach((file, index) => {
                const col = document.createElement("div");
                col.className = "col-12";
                col.innerHTML =
                    '<div class="border rounded p-3">' +
                    '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">' +
                    "<div>" +
                    "<strong>" +
                    escapeHtml(file.name) +
                    "</strong>" +
                    '<div class="text-muted small">' +
                    Math.round(file.size / 1024) +
                    " KB</div>" +
                    "</div>" +
                    '<span class="badge bg-secondary">#' +
                    (index + 1) +
                    "</span>" +
                    "</div>" +
                    '<div class="text-muted small">Tags will be applied from the "Entity Tags (Apply to All Files)" section above.</div>' +
                    "</div>";
                fileList.appendChild(col);
            });
        }

        function showStatus(type, message, detailsHtml) {
            const alert = document.createElement("div");
            alert.className = "alert alert-" + type;
            alert.innerHTML =
                '<div class="fw-semibold mb-1">' +
                escapeHtml(message) +
                "</div>" +
                (detailsHtml || "");
            statusBox.innerHTML = "";
            statusBox.appendChild(alert);
        }

        function buildDetails(results) {
            if (!results.length) {
                return "";
            }

            let detailHtml = '<ul class="mb-0 ps-3">';
            results.forEach((result) => {
                const ok = Boolean(result && result.success);
                const statusClass = ok ? "text-success" : "text-danger";
                const icon = ok ? "bi-check-circle" : "bi-exclamation-circle";
                const fileName =
                    result && result.name ? escapeHtml(result.name) : "unnamed";
                const duplicate =
                    result && result.existing ? " (duplicate)" : "";
                const error =
                    result && result.error
                        ? ": " + escapeHtml(result.error)
                        : "";
                detailHtml +=
                    '<li class="' +
                    statusClass +
                    '"><i class="bi ' +
                    icon +
                    '"></i> ' +
                    fileName +
                    duplicate +
                    error +
                    "</li>";
            });
            detailHtml += "</ul>";

            return detailHtml;
        }

        async function uploadChunk(chunkFiles, chunkIndex) {
            const formData = new FormData(formElement);
            formData.delete("uploads[]");
            formData.delete("uploads");

            chunkFiles.forEach((file) => {
                formData.append("uploads[]", file, file.name);
            });

            const response = await fetch(
                formElement.action || "/admin/images/bulk-upload",
                {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                },
            );

            const contentType = (
                response.headers.get("content-type") || ""
            ).toLowerCase();
            if (!contentType.includes("application/json")) {
                const text = await response.text();
                const snippet = text.slice(0, 500);
                console.error("Non-JSON response during bulk upload", {
                    status: response.status,
                    chunkIndex,
                    snippet,
                });
                const err = new Error("non-json");
                err.name = "NonJsonResponseError";
                err.status = response.status;
                err.snippet = snippet;
                throw err;
            }

            return response.json();
        }

        const onUploadsChanged = (event) => {
            renderFileRows(event.target.files || []);
        };

        const onUploadClicked = async () => {
            const files = Array.from(uploadsInput.files || []);
            if (!files.length) {
                showStatus("danger", "Please choose at least one image file.");
                return;
            }

            const label = uploadBtn.querySelector(".label");
            const spinner = uploadBtn.querySelector(".spinner-border");
            if (label) {
                label.classList.add("d-none");
            }
            if (spinner) {
                spinner.classList.remove("d-none");
            }
            uploadBtn.setAttribute("disabled", "disabled");
            statusBox.innerHTML = "";

            const allResults = [];

            try {
                for (
                    let index = 0;
                    index < files.length;
                    index += BULK_CHUNK_SIZE
                ) {
                    const chunk = files.slice(index, index + BULK_CHUNK_SIZE);
                    const chunkNumber = Math.floor(index / BULK_CHUNK_SIZE) + 1;
                    const data = await uploadChunk(chunk, chunkNumber);
                    if (Array.isArray(data.results)) {
                        allResults.push(...data.results);
                    } else if (data && data.error) {
                        allResults.push({
                            success: false,
                            name: "batch-" + chunkNumber,
                            error: data.error,
                        });
                    }
                }

                const successes = allResults.filter(
                    (result) => result && result.success,
                );
                const failures = allResults.filter(
                    (result) => !result || !result.success,
                );
                const detailHtml = buildDetails(allResults);

                if (successes.length && failures.length === 0) {
                    showStatus(
                        "success",
                        "All images uploaded successfully.",
                        detailHtml,
                    );
                } else if (successes.length && failures.length) {
                    showStatus(
                        "warning",
                        "Some images uploaded; some failed.",
                        detailHtml,
                    );
                } else {
                    showStatus("danger", "Upload failed.", detailHtml);
                }
            } catch (error) {
                if (error && error.name === "NonJsonResponseError") {
                    const status = Number(error.status || 0);
                    if (status === 413) {
                        showStatus(
                            "danger",
                            "Upload request was too large for the server.",
                            '<div class="small">Try fewer images per batch or smaller files.</div>',
                        );
                    } else {
                        showStatus(
                            "danger",
                            "Server returned invalid response (expected JSON, got HTML).",
                            '<div class="small">HTTP status: ' +
                                escapeHtml(String(status || "unknown")) +
                                "</div>",
                        );
                    }
                } else {
                    showStatus(
                        "danger",
                        "Unexpected error while uploading.",
                        '<div class="small">' +
                            escapeHtml(
                                error && error.message
                                    ? error.message
                                    : "Unknown error",
                            ) +
                            "</div>",
                    );
                }
            } finally {
                if (label) {
                    label.classList.remove("d-none");
                }
                if (spinner) {
                    spinner.classList.add("d-none");
                }
                uploadBtn.removeAttribute("disabled");
            }
        };

        uploadsInput.addEventListener("change", onUploadsChanged);
        uploadBtn.addEventListener("click", onUploadClicked);

        replaceCleanup("bulkUpload", function () {
            uploadsInput.removeEventListener("change", onUploadsChanged);
            uploadBtn.removeEventListener("click", onUploadClicked);
            delete formElement.dataset.bulkUploadBound;
        });

        renderFileRows(uploadsInput.files || []);
    }

    function initCropThumbPage() {
        const img = document.getElementById("crop-image");
        const container = document.getElementById("crop-container");
        const overlay = document.getElementById("crop-overlay");
        const previewCanvas = document.getElementById("preview-canvas");

        if (!img || !container || !overlay || !previewCanvas) {
            return;
        }
        if (container.dataset.cropThumbBound === "1") {
            return;
        }
        container.dataset.cropThumbBound = "1";

        const resizeHandle = overlay.querySelector(".resize-handle");

        let cropData = { x: 0, y: 0, width: 0, height: 0 };
        let isDragging = false;
        let isResizing = false;
        let dragStart = { x: 0, y: 0 };
        let imgNaturalWidth = 0;
        let imgDisplayWidth = 0;
        let imgDisplayHeight = 0;

        function getScale() {
            if (!imgDisplayWidth) {
                return 1;
            }

            return imgNaturalWidth / imgDisplayWidth;
        }

        function updatePreview() {
            const scale = getScale();
            const srcX = Math.round(cropData.x * scale);
            const srcY = Math.round(cropData.y * scale);
            const srcWidth = Math.round(cropData.width * scale);
            const srcHeight = Math.round(cropData.height * scale);

            if (srcWidth <= 0 || srcHeight <= 0 || !img.complete) {
                return;
            }

            const ctx = previewCanvas.getContext("2d");
            if (!ctx) {
                return;
            }

            ctx.clearRect(0, 0, 150, 150);
            ctx.drawImage(img, srcX, srcY, srcWidth, srcHeight, 0, 0, 150, 150);
        }

        function updateFormFields() {
            const scale = getScale();
            const inputX = document.getElementById("crop_x");
            const inputY = document.getElementById("crop_y");
            const inputW = document.getElementById("crop_width");
            const inputH = document.getElementById("crop_height");

            if (!inputX || !inputY || !inputW || !inputH) {
                return;
            }

            inputX.value = String(Math.round(cropData.x * scale));
            inputY.value = String(Math.round(cropData.y * scale));
            inputW.value = String(Math.round(cropData.width * scale));
            inputH.value = String(Math.round(cropData.height * scale));
        }

        function updateOverlay() {
            overlay.style.left = cropData.x + "px";
            overlay.style.top = cropData.y + "px";
            overlay.style.width = cropData.width + "px";
            overlay.style.height = cropData.height + "px";
            overlay.style.display =
                cropData.width > 0 && cropData.height > 0 ? "block" : "none";

            updatePreview();
            updateFormFields();
        }

        function initCrop() {
            const rect = img.getBoundingClientRect();
            imgDisplayWidth = rect.width;
            imgDisplayHeight = rect.height;
            imgNaturalWidth = img.naturalWidth;

            const size = Math.min(imgDisplayWidth, imgDisplayHeight);
            cropData = {
                x: Math.floor((imgDisplayWidth - size) / 2),
                y: Math.floor((imgDisplayHeight - size) / 2),
                width: size,
                height: size,
            };

            updateOverlay();
        }

        function resetCrop() {
            initCrop();
        }

        function onMouseDown(event) {
            const containerRect = container.getBoundingClientRect();
            const mouseX = event.clientX - containerRect.left;
            const mouseY = event.clientY - containerRect.top;

            if (resizeHandle && event.target === resizeHandle) {
                isResizing = true;
                dragStart = { x: mouseX, y: mouseY };

                return;
            }

            if (
                mouseX >= cropData.x &&
                mouseX <= cropData.x + cropData.width &&
                mouseY >= cropData.y &&
                mouseY <= cropData.y + cropData.height
            ) {
                isDragging = true;
                dragStart = { x: mouseX - cropData.x, y: mouseY - cropData.y };

                return;
            }

            isDragging = true;
            cropData.x = mouseX;
            cropData.y = mouseY;
            cropData.width = 1;
            cropData.height = 1;
            dragStart = { x: mouseX, y: mouseY };
        }

        function onMouseMove(event) {
            if (!isDragging && !isResizing) {
                return;
            }
            if (!document.body.contains(container)) {
                return;
            }

            event.preventDefault();

            const containerRect = container.getBoundingClientRect();
            const mouseX = event.clientX - containerRect.left;
            const mouseY = event.clientY - containerRect.top;

            if (isDragging && !isResizing) {
                if (cropData.width === 1 && cropData.height === 1) {
                    const dx = mouseX - dragStart.x;
                    const dy = mouseY - dragStart.y;
                    const size = Math.max(
                        20,
                        Math.min(Math.abs(dx), Math.abs(dy)),
                    );

                    cropData.x = dx < 0 ? dragStart.x - size : dragStart.x;
                    cropData.y = dy < 0 ? dragStart.y - size : dragStart.y;

                    cropData.x = Math.max(
                        0,
                        Math.min(cropData.x, imgDisplayWidth - size),
                    );
                    cropData.y = Math.max(
                        0,
                        Math.min(cropData.y, imgDisplayHeight - size),
                    );
                    cropData.width = size;
                    cropData.height = size;
                } else {
                    let newX = mouseX - dragStart.x;
                    let newY = mouseY - dragStart.y;

                    newX = Math.max(
                        0,
                        Math.min(newX, imgDisplayWidth - cropData.width),
                    );
                    newY = Math.max(
                        0,
                        Math.min(newY, imgDisplayHeight - cropData.height),
                    );

                    cropData.x = newX;
                    cropData.y = newY;
                }
            } else if (isResizing) {
                const deltaX = mouseX - cropData.x;
                const deltaY = mouseY - cropData.y;
                let newSize = Math.min(deltaX, deltaY);
                newSize = Math.max(20, newSize);
                newSize = Math.min(
                    newSize,
                    imgDisplayWidth - cropData.x,
                    imgDisplayHeight - cropData.y,
                );
                cropData.width = newSize;
                cropData.height = newSize;
            }

            updateOverlay();
        }

        function onMouseUp() {
            isDragging = false;
            isResizing = false;
        }

        function onImageLoad() {
            initCrop();
        }

        container.addEventListener("mousedown", onMouseDown);
        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
        img.addEventListener("load", onImageLoad);

        if (img.complete) {
            initCrop();
        }

        window.resetCrop = resetCrop;

        replaceCleanup("cropThumb", function () {
            container.removeEventListener("mousedown", onMouseDown);
            document.removeEventListener("mousemove", onMouseMove);
            document.removeEventListener("mouseup", onMouseUp);
            img.removeEventListener("load", onImageLoad);

            if (window.resetCrop === resetCrop) {
                delete window.resetCrop;
            }
            delete container.dataset.cropThumbBound;
        });
    }

    function ensureCropSelectorScript() {
        if (typeof window.CropSelector === "function") {
            return Promise.resolve();
        }
        if (state.cropSelectorPromise) {
            return state.cropSelectorPromise;
        }

        state.cropSelectorPromise = new Promise((resolve, reject) => {
            const existing = document.querySelector(
                'script[src="/js/crop-selector.js"]',
            );
            if (existing && typeof window.CropSelector === "function") {
                resolve();

                return;
            }

            const script = existing || document.createElement("script");
            if (!existing) {
                script.src = "/js/crop-selector.js";
                document.head.appendChild(script);
            }

            script.addEventListener("load", () => {
                resolve();
            });
            script.addEventListener("error", () => {
                reject(new Error("Unable to load crop-selector.js"));
            });
        });

        return state.cropSelectorPromise;
    }

    async function initManipulatePage() {
        const sourceImage = document.getElementById("sourceImage");
        const previewCanvas = document.getElementById("previewCanvas");

        if (!sourceImage || !previewCanvas) {
            return;
        }
        if (sourceImage.dataset.manipulateBound === "1") {
            return;
        }
        sourceImage.dataset.manipulateBound = "1";

        try {
            await ensureCropSelectorScript();
        } catch (err) {
            console.error(err);
            return;
        }

        if (
            !document.body.contains(sourceImage) ||
            typeof window.CropSelector !== "function"
        ) {
            return;
        }

        let cropSelector = null;

        function updateCropInputs(crop) {
            const inputX = document.getElementById("crop-x");
            const inputY = document.getElementById("crop-y");
            const inputW = document.getElementById("crop-width");
            const inputH = document.getElementById("crop-height");
            if (!inputX || !inputY || !inputW || !inputH) {
                return;
            }

            inputX.value = String(crop.x);
            inputY.value = String(crop.y);
            inputW.value = String(crop.width);
            inputH.value = String(crop.height);
        }

        function setRotation(degrees) {
            const rangeEl = document.getElementById("rotate-range");
            const inputEl = document.getElementById("rotate");
            if (rangeEl) {
                rangeEl.value = String(degrees);
            }
            if (inputEl) {
                inputEl.value = String(degrees);
            }

            if (cropSelector) {
                cropSelector.setRotation(parseFloat(degrees) || 0);
            }
        }

        function setAspectRatio(ratio, btn) {
            if (cropSelector) {
                cropSelector.setAspectRatio(ratio);
            }

            document
                .querySelectorAll('[onclick^="setAspectRatio"]')
                .forEach((button) => button.classList.remove("active"));

            if (ratio === null) {
                const freeBtn = document.getElementById("ratio-free");
                if (freeBtn) {
                    freeBtn.classList.add("active");
                }
            } else if (btn) {
                btn.classList.add("active");
            }
        }

        function resetAll() {
            const rangeEl = document.getElementById("rotate-range");
            const inputEl = document.getElementById("rotate");
            if (rangeEl) {
                rangeEl.value = "0";
            }
            if (inputEl) {
                inputEl.value = "0";
            }

            setAspectRatio(null);

            if (
                sourceImage.complete &&
                sourceImage.naturalWidth &&
                cropSelector
            ) {
                cropSelector.setCropBox(
                    0,
                    0,
                    sourceImage.naturalWidth,
                    sourceImage.naturalHeight,
                );
            }
        }

        const onRangeInput = function () {
            const inputEl = document.getElementById("rotate");
            if (inputEl) {
                inputEl.value = this.value;
            }
            if (cropSelector) {
                cropSelector.setRotation(parseFloat(this.value) || 0);
            }
        };

        const onNumberInput = function () {
            const rangeEl = document.getElementById("rotate-range");
            if (rangeEl) {
                rangeEl.value = this.value;
            }
            if (cropSelector) {
                cropSelector.setRotation(parseFloat(this.value) || 0);
            }
        };

        cropSelector = new window.CropSelector("previewCanvas", "sourceImage", {
            onCropChange: updateCropInputs,
        });

        const rangeEl = document.getElementById("rotate-range");
        const inputEl = document.getElementById("rotate");
        if (rangeEl) {
            rangeEl.addEventListener("input", onRangeInput);
        }
        if (inputEl) {
            inputEl.addEventListener("input", onNumberInput);
        }

        const freeBtn = document.getElementById("ratio-free");
        if (freeBtn) {
            freeBtn.classList.add("active");
        }

        window.setRotation = setRotation;
        window.setAspectRatio = setAspectRatio;
        window.resetAll = resetAll;

        replaceCleanup("manipulate", function () {
            if (rangeEl) {
                rangeEl.removeEventListener("input", onRangeInput);
            }
            if (inputEl) {
                inputEl.removeEventListener("input", onNumberInput);
            }

            if (window.setRotation === setRotation) {
                delete window.setRotation;
            }
            if (window.setAspectRatio === setAspectRatio) {
                delete window.setAspectRatio;
            }
            if (window.resetAll === resetAll) {
                delete window.resetAll;
            }
            delete sourceImage.dataset.manipulateBound;
        });
    }

    function initAdminImagePages() {
        initBulkUploadPage();
        initCropThumbPage();
        initManipulatePage();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAdminImagePages);
    } else {
        initAdminImagePages();
    }

    document.addEventListener("turbo:load", initAdminImagePages);
    document.addEventListener("turbo:frame-load", initAdminImagePages);
    document.addEventListener("turbo:before-cache", cleanupAll);
})();
