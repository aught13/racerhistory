/**
 * Utilities to support the person image selector used in admin person forms.
 * Exported so we can unit test the upload and preview logic.
 */
(function (root, factory) {
    if (typeof module !== "undefined" && module && module.exports) {
        // Node/CommonJS (Jest) environment
        module.exports = factory();
    } else {
        // Browser global
        root.PersonImage = factory();
    }
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
    "use strict";

    async function uploadFile(
        file,
        uploadUrl = "/admin/images/upload",
        csrfToken = null,
    ) {
        const formData = new FormData();
        formData.append("upload", file, file.name || "file");

        const headers = {};
        if (csrfToken) {
            headers["X-CSRF-Token"] = csrfToken;
        }

        const response = await fetch(uploadUrl, {
            method: "POST",
            body: formData,
            credentials: "same-origin",
            headers,
        });

        // Try parsing JSON like the app expects
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch {
            throw new Error("Invalid JSON response");
        }
    }

    function setPreviewFromId(imageSource, previewImgElem) {
        if (!imageSource || !previewImgElem) return;

        let url = "";
        if (typeof imageSource === "string") {
            url = imageSource.includes("/") ? imageSource : "";
        } else if (typeof imageSource === "object") {
            url =
                imageSource.thumbnail_url ||
                imageSource.url ||
                imageSource.direct_url ||
                "";
        }

        if (!url) {
            return;
        }

        previewImgElem.src = url;
        // Ensure container visible if wrapped
        if (previewImgElem.parentElement) {
            previewImgElem.parentElement.style.display = "block";
        }
    }

    // Minimal DOM wiring helper (keeps implementation small; templates may still use their inline init)
    function initPersonImageSelector(opts) {
        const selectBtn = document.getElementById(opts.selectBtnId);
        const imageField = document.getElementById(opts.fieldId);
        const preview = document.getElementById(opts.previewId);
        const csrf = opts.csrf || null;
        if (!selectBtn || !imageField || !preview) return;

        selectBtn.addEventListener("click", function (e) {
            e.preventDefault();
            const input = document.createElement("input");
            input.type = "file";
            input.accept = "image/*";
            input.onchange = async function () {
                if (!input.files || !input.files[0]) return;
                selectBtn.disabled = true;
                const file = input.files[0];
                try {
                    const json = await uploadFile(
                        file,
                        opts.uploadUrl || "/admin/images/upload",
                        csrf,
                    );
                    if (json && json.success && json.image) {
                        imageField.value = json.image.id;
                        const img = preview.querySelector("img");
                        if (img) {
                            setPreviewFromId(
                                json.image.thumbnail_url ||
                                    json.image.url ||
                                    json.image,
                                img,
                            );
                        }
                    } else {
                        console.error("Upload failed", json);
                        alert("Upload failed");
                    }
                } catch (_err) {
                    console.error("Upload error", _err);
                    alert("Upload failed: " + _err.message);
                } finally {
                    selectBtn.disabled = false;
                    selectBtn.textContent = opts.buttonText || "Select Image";
                }
            };
            input.click();
        });
    }

    return {
        uploadFile,
        setPreviewFromId,
        initPersonImageSelector,
    };
});
