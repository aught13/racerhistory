beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

import { jest } from "@jest/globals";
// person-image.js error branch coverage tests

let personImage;
describe("person-image error branches", () => {
    beforeEach(async () => {
        jest.resetModules();
        const mod = await import("../../legacy/person-image.js");
        personImage = mod.default || mod;

        document.body.innerHTML = "";
        jest.resetAllMocks();
        // Clear any global fetch mock
        delete global.fetch;
    });

    test("uploadFile - upload failure with fetch error", async () => {
        // Mock fetch to reject
        global.fetch = jest.fn(() =>
            Promise.reject(new Error("Network error")),
        );

        const blob = new Blob(["test"], { type: "image/png" });
        const file = new File([blob], "test.png", { type: "image/png" });

        await expect(personImage.uploadFile(file)).rejects.toThrow(
            "Network error",
        );
    });

    test("uploadFile - invalid mime type response", async () => {
        // Mock server response with invalid JSON
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () => Promise.resolve("<html>Server Error</html>"),
            }),
        );

        const blob = new Blob(["test"], { type: "image/png" });
        const file = new File([blob], "test.png", { type: "image/png" });

        await expect(personImage.uploadFile(file)).rejects.toThrow(
            "Invalid JSON response",
        );
    });

    test("uploadFile - empty file handling", async () => {
        // Mock successful response
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () =>
                    Promise.resolve(
                        JSON.stringify({
                            success: false,
                            error: "File is empty",
                        }),
                    ),
            }),
        );

        const emptyBlob = new Blob([], { type: "image/png" });
        const emptyFile = new File([emptyBlob], "empty.png", {
            type: "image/png",
        });

        const result = await personImage.uploadFile(emptyFile);
        expect(result.success).toBe(false);
        expect(result.error).toBe("File is empty");
    });

    test("uploadFile - server error response", async () => {
        // Mock server error response
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () =>
                    Promise.resolve(
                        JSON.stringify({
                            success: false,
                            error: "Invalid file type",
                        }),
                    ),
            }),
        );

        const blob = new Blob(["test"], { type: "text/plain" });
        const file = new File([blob], "test.txt", { type: "text/plain" });

        const result = await personImage.uploadFile(file);
        expect(result.success).toBe(false);
        expect(result.error).toBe("Invalid file type");
    });

    test("initPersonImageSelector - missing required elements", () => {
        // Empty DOM
        document.body.innerHTML = "<div></div>";

        // Should handle missing elements gracefully
        expect(() => {
            personImage.initPersonImageSelector({
                selectBtnId: "missing-btn",
                fieldId: "missing-field",
                previewId: "missing-preview",
            });
        }).not.toThrow();
    });

    test("initPersonImageSelector - upload failure handling with alert", async () => {
        document.body.innerHTML = `
            <input id="img-field" value="" />
            <div id="preview"><img id="pvimg" src="" /></div>
            <button id="select-btn">Select</button>
        `;

        // Mock upload failure
        global.fetch = jest.fn(() =>
            Promise.reject(new Error("Upload failed")),
        );

        // Mock alert to capture error display
        global.alert = jest.fn();

        // Mock file input creation and selection
        const origCreate = document.createElement.bind(document);
        document.createElement = function (tag) {
            const el = origCreate(tag);
            if (tag === "input") {
                el.click = function () {
                    const fileBlob = new Blob(["data"], { type: "image/png" });
                    Object.defineProperty(el, "files", {
                        value: [
                            new File([fileBlob], "test.png", {
                                type: "image/png",
                            }),
                        ],
                        configurable: true,
                    });
                    setTimeout(() => el.onchange && el.onchange());
                };
            }
            return el;
        };

        personImage.initPersonImageSelector({
            selectBtnId: "select-btn",
            fieldId: "img-field",
            previewId: "preview",
            uploadUrl: "/admin/images/upload",
        });

        const selectBtn = document.getElementById("select-btn");
        selectBtn.click();

        // Wait for async operations
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(global.alert).toHaveBeenCalledWith(
            "Upload failed: Upload failed",
        );
        expect(selectBtn.disabled).toBe(false); // Should re-enable button

        // Restore
        document.createElement = origCreate;
        delete global.alert;
    });

    test("initPersonImageSelector - upload success but no image in response", async () => {
        document.body.innerHTML = `
            <input id="img-field" value="" />
            <div id="preview"><img id="pvimg" src="" /></div>
            <button id="select-btn">Select</button>
        `;

        // Mock successful response but missing image data
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () => Promise.resolve(JSON.stringify({ success: true })),
            }),
        );

        global.alert = jest.fn();

        const origCreate = document.createElement.bind(document);
        document.createElement = function (tag) {
            const el = origCreate(tag);
            if (tag === "input") {
                el.click = function () {
                    const fileBlob = new Blob(["data"], { type: "image/png" });
                    Object.defineProperty(el, "files", {
                        value: [
                            new File([fileBlob], "test.png", {
                                type: "image/png",
                            }),
                        ],
                        configurable: true,
                    });
                    setTimeout(() => el.onchange && el.onchange());
                };
            }
            return el;
        };

        personImage.initPersonImageSelector({
            selectBtnId: "select-btn",
            fieldId: "img-field",
            previewId: "preview",
        });

        document.getElementById("select-btn").click();

        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(global.alert).toHaveBeenCalledWith("Upload failed");

        document.createElement = origCreate;
        delete global.alert;
    });

    test("initPersonImageSelector - no files selected", async () => {
        document.body.innerHTML = `
            <input id="img-field" value="" />
            <div id="preview"><img id="pvimg" src="" /></div>
            <button id="select-btn">Select</button>
        `;

        const origCreate = document.createElement.bind(document);
        document.createElement = function (tag) {
            const el = origCreate(tag);
            if (tag === "input") {
                el.click = function () {
                    // No files selected - empty FileList
                    Object.defineProperty(el, "files", {
                        value: [],
                        configurable: true,
                    });
                    setTimeout(() => el.onchange && el.onchange());
                };
            }
            return el;
        };

        personImage.initPersonImageSelector({
            selectBtnId: "select-btn",
            fieldId: "img-field",
            previewId: "preview",
        });

        const selectBtn = document.getElementById("select-btn");
        selectBtn.click();

        await new Promise((resolve) => setTimeout(resolve, 10));

        // Button should not be disabled since no upload occurred
        expect(selectBtn.disabled).toBe(false);

        document.createElement = origCreate;
    });

    test("setPreviewFromId - no imageId provided", () => {
        document.body.innerHTML =
            '<div id="preview"><img id="pimg" src=""/></div>';
        const img = document.getElementById("pimg");
        const originalSrc = img.src;

        // Should return early with no imageId
        personImage.setPreviewFromId(null, img);
        personImage.setPreviewFromId(0, img);
        personImage.setPreviewFromId("", img);

        // Image src should not change
        expect(img.src).toBe(originalSrc);
    });

    test("setPreviewFromId - missing parent element", () => {
        // Create img element without parent
        const img = document.createElement("img");

        // Should not throw when parentElement is null
        expect(() => {
            personImage.setPreviewFromId("/img/storage/5.webp", img);
        }).not.toThrow();

        expect(img.src).toContain("/img/storage/5.webp");
    });
});
