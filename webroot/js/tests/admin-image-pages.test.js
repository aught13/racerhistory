/** @jest-environment jsdom */

import { jest } from "@jest/globals";

function setupBulkUploadDom() {
    document.body.innerHTML = `
        <form id="bulkUploadForm" action="/admin/images/bulk-upload" enctype="multipart/form-data">
            <input id="uploads" name="uploads[]" type="file" multiple />
            <input name="common_tags" value="team,season" />
            <div id="fileList"></div>
            <button id="uploadAll" type="button" disabled>
                <span class="label">Upload Selected</span>
                <span class="spinner-border d-none"></span>
            </button>
            <div id="uploadStatus"></div>
        </form>
    `;
}

function setupCropThumbDom() {
    document.body.innerHTML = `
        <div id="crop-container">
            <img id="crop-image" alt="crop" />
            <div id="crop-overlay"><div class="resize-handle"></div></div>
        </div>
        <canvas id="preview-canvas" width="150" height="150"></canvas>
        <input id="crop_x" />
        <input id="crop_y" />
        <input id="crop_width" />
        <input id="crop_height" />
    `;
}

function setInputFiles(input, files) {
    Object.defineProperty(input, "files", {
        value: files,
        configurable: true,
    });
}

async function flushAsync(cycles = 4) {
    for (let i = 0; i < cycles; i += 1) {
        await Promise.resolve();
        await new Promise((resolve) => setTimeout(resolve, 0));
    }
}

function jsonResponse(payload) {
    return {
        status: 200,
        headers: {
            get: () => "application/json; charset=utf-8",
        },
        json: async () => payload,
        text: async () => JSON.stringify(payload),
    };
}

describe("admin-image-pages.js", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        delete window.resetCrop;
        delete window.setRotation;
        delete window.setAspectRatio;
        delete window.resetAll;
        delete window.CropSelector;
        global.fetch = undefined;
    });

    afterEach(() => {
        document.dispatchEvent(new Event("turbo:before-cache"));
        document.body.innerHTML = "";
        delete window.resetCrop;
        delete window.setRotation;
        delete window.setAspectRatio;
        delete window.resetAll;
        delete window.CropSelector;
        global.fetch = undefined;
        jest.restoreAllMocks();
    });

    test("bulk upload renders selected files and enables upload button", async () => {
        setupBulkUploadDom();
        await import("../admin-image-pages.js");

        const uploadsInput = document.getElementById("uploads");
        const uploadBtn = document.getElementById("uploadAll");
        const fileList = document.getElementById("fileList");

        const files = [
            new File(["a"], "alpha.png", { type: "image/png" }),
            new File(["b"], "beta.png", { type: "image/png" }),
        ];
        setInputFiles(uploadsInput, files);
        uploadsInput.dispatchEvent(new Event("change", { bubbles: true }));

        expect(uploadBtn.hasAttribute("disabled")).toBe(false);
        expect(fileList.textContent).toContain("alpha.png");
        expect(fileList.textContent).toContain("beta.png");
    });

    test("bulk upload sends files in chunks of three", async () => {
        setupBulkUploadDom();
        global.fetch = jest.fn().mockImplementation(async (_url, options) => {
            const submitted = options.body.getAll("uploads[]");

            return jsonResponse({
                results: submitted.map((file) => ({
                    success: true,
                    name: file.name,
                })),
            });
        });

        await import("../admin-image-pages.js");

        const uploadsInput = document.getElementById("uploads");
        const uploadBtn = document.getElementById("uploadAll");

        const files = [
            new File(["1"], "one.png", { type: "image/png" }),
            new File(["2"], "two.png", { type: "image/png" }),
            new File(["3"], "three.png", { type: "image/png" }),
            new File(["4"], "four.png", { type: "image/png" }),
        ];
        setInputFiles(uploadsInput, files);
        uploadsInput.dispatchEvent(new Event("change", { bubbles: true }));
        uploadBtn.click();

        await flushAsync(8);

        expect(global.fetch).toHaveBeenCalledTimes(2);
        expect(
            global.fetch.mock.calls[0][1].body.getAll("uploads[]"),
        ).toHaveLength(3);
        expect(
            global.fetch.mock.calls[1][1].body.getAll("uploads[]"),
        ).toHaveLength(1);
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "All images uploaded successfully.",
        );
    });

    test("bulk upload shows specific error on 413 non-JSON response", async () => {
        setupBulkUploadDom();
        global.fetch = jest.fn().mockResolvedValue({
            status: 413,
            headers: {
                get: () => "text/html",
            },
            json: async () => {
                throw new Error("not used");
            },
            text: async () => "<html><body>Payload Too Large</body></html>",
        });

        await import("../admin-image-pages.js");

        const uploadsInput = document.getElementById("uploads");
        const uploadBtn = document.getElementById("uploadAll");
        const files = [new File(["123"], "large.png", { type: "image/png" })];

        setInputFiles(uploadsInput, files);
        uploadsInput.dispatchEvent(new Event("change", { bubbles: true }));
        uploadBtn.click();

        await flushAsync(6);

        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Upload request was too large for the server.",
        );
    });

    test("crop and manipulate page initializers run on first load and clean up", async () => {
        setupCropThumbDom();

        const cropImage = document.getElementById("crop-image");
        const cropCanvas = document.getElementById("preview-canvas");
        Object.defineProperty(cropImage, "complete", {
            value: true,
            configurable: true,
        });
        Object.defineProperty(cropImage, "naturalWidth", {
            value: 240,
            configurable: true,
        });
        cropImage.getBoundingClientRect = () => ({
            width: 120,
            height: 80,
            left: 0,
            top: 0,
            right: 120,
            bottom: 80,
            x: 0,
            y: 0,
            toJSON: () => ({}),
        });
        cropCanvas.getContext = jest.fn(() => ({
            clearRect: jest.fn(),
            drawImage: jest.fn(),
        }));

        document.body.insertAdjacentHTML(
            "beforeend",
            `
                <img id="sourceImage" alt="source" />
                <canvas id="previewCanvas"></canvas>
                <input id="rotate-range" type="range" value="0" />
                <input id="rotate" type="number" value="0" />
                <button id="ratio-free" type="button">Free</button>
                <button type="button" onclick="setAspectRatio(1, this)">1:1</button>
                <input id="crop-x" />
                <input id="crop-y" />
                <input id="crop-width" />
                <input id="crop-height" />
            `,
        );

        const sourceImage = document.getElementById("sourceImage");
        Object.defineProperty(sourceImage, "complete", {
            value: true,
            configurable: true,
        });
        Object.defineProperty(sourceImage, "naturalWidth", {
            value: 320,
            configurable: true,
        });
        Object.defineProperty(sourceImage, "naturalHeight", {
            value: 240,
            configurable: true,
        });

        window.CropSelector = class CropSelectorMock {
            constructor(_canvasId, _imageId, options) {
                this.options = options;
                if (
                    this.options &&
                    typeof this.options.onCropChange === "function"
                ) {
                    this.options.onCropChange({
                        x: 7,
                        y: 9,
                        width: 120,
                        height: 90,
                    });
                }
            }

            setRotation() {}
            setAspectRatio() {}
            setCropBox() {}
        };

        await import("../admin-image-pages.js");
        await flushAsync(4);

        expect(document.getElementById("crop-overlay").style.display).toBe(
            "block",
        );
        expect(document.getElementById("crop_width").value).not.toBe("0");
        expect(typeof window.resetCrop).toBe("function");

        expect(typeof window.setRotation).toBe("function");
        expect(typeof window.setAspectRatio).toBe("function");
        expect(typeof window.resetAll).toBe("function");
        expect(document.getElementById("crop-width").value).toBe("120");
        expect(document.getElementById("crop-height").value).toBe("90");

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(window.resetCrop).toBeUndefined();
        expect(window.setRotation).toBeUndefined();
        expect(window.setAspectRatio).toBeUndefined();
        expect(window.resetAll).toBeUndefined();
    });
});
