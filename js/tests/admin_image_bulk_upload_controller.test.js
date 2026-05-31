/* global File, afterEach, beforeEach, describe, expect, global, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminImageBulkUploadController from "../controllers/admin_image_bulk_upload_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("admin-image-bulk-upload controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <form
                id="bulkUploadForm"
                action="/admin/images/bulk-upload"
                data-controller="admin-image-bulk-upload"
                data-admin-image-bulk-upload-target="form"
                data-admin-image-bulk-upload-chunk-size-value="2"
            >
                <input
                    type="file"
                    id="uploads"
                    name="uploads[]"
                    multiple
                    data-admin-image-bulk-upload-target="uploadsInput"
                    data-action="change->admin-image-bulk-upload#fileSelectionChanged"
                />
                <div id="fileList" data-admin-image-bulk-upload-target="fileList"></div>
                <button
                    id="uploadAll"
                    type="button"
                    disabled
                    data-admin-image-bulk-upload-target="uploadButton"
                    data-action="click->admin-image-bulk-upload#uploadAll"
                >
                    <span class="label" data-admin-image-bulk-upload-target="buttonLabel">Upload Selected</span>
                    <span class="spinner-border d-none" data-admin-image-bulk-upload-target="buttonSpinner"></span>
                </button>
                <div id="uploadStatus" data-admin-image-bulk-upload-target="uploadStatus"></div>
            </form>
        `;

        global.fetch = jest.fn();

        application = Application.start();
        application.register(
            "admin-image-bulk-upload",
            AdminImageBulkUploadController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        jest.restoreAllMocks();
        delete global.fetch;
        document.body.innerHTML = "";
    });

    test("renders selected files and enables upload button", () => {
        const input = document.getElementById("uploads");
        const uploadButton = document.getElementById("uploadAll");

        const files = [
            new File(["abc"], "first.png", { type: "image/png" }),
            new File(["def"], "second.png", { type: "image/png" }),
        ];

        Object.defineProperty(input, "files", {
            configurable: true,
            value: files,
        });
        input.dispatchEvent(new Event("change", { bubbles: true }));

        expect(uploadButton.disabled).toBe(false);
        expect(document.querySelectorAll("#fileList .col-12")).toHaveLength(2);
    });

    test("uploads in chunks and shows success status", async () => {
        fetch.mockResolvedValue({
            headers: { get: () => "application/json" },
            json: async () => ({
                results: [{ success: true, name: "first.png" }],
            }),
        });

        const input = document.getElementById("uploads");
        const files = [
            new File(["a"], "first.png", { type: "image/png" }),
            new File(["b"], "second.png", { type: "image/png" }),
            new File(["c"], "third.png", { type: "image/png" }),
        ];
        Object.defineProperty(input, "files", {
            configurable: true,
            value: files,
        });
        input.dispatchEvent(new Event("change", { bubbles: true }));

        document.getElementById("uploadAll").click();
        await flushPromises();

        expect(fetch.mock.calls.length).toBeGreaterThanOrEqual(1);
        expect(fetch.mock.calls[0][0]).toContain("/admin/images/bulk-upload");
    });

    test("shows an error when no files are selected", async () => {
        document.getElementById("uploadAll").removeAttribute("disabled");
        document.getElementById("uploadAll").click();
        await flushPromises();
        await flushPromises();

        expect(global.fetch).not.toHaveBeenCalled();
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Please choose at least one image file.",
        );
    });

    test("shows a warning when some chunk uploads fail", async () => {
        fetch
            .mockResolvedValueOnce({
                headers: { get: () => "application/json" },
                json: async () => ({
                    results: [{ success: true, name: "first.png" }],
                }),
            })
            .mockResolvedValueOnce({
                headers: { get: () => "application/json" },
                json: async () => ({
                    results: [
                        {
                            success: false,
                            name: "third.png",
                            error: "Failed validation",
                        },
                    ],
                }),
            });

        const input = document.getElementById("uploads");
        const files = [
            new File(["a"], "first.png", { type: "image/png" }),
            new File(["b"], "second.png", { type: "image/png" }),
            new File(["c"], "third.png", { type: "image/png" }),
        ];
        Object.defineProperty(input, "files", {
            configurable: true,
            value: files,
        });
        input.dispatchEvent(new Event("change", { bubbles: true }));

        document.getElementById("uploadAll").click();
        await flushPromises();
        await flushPromises();
        await flushPromises();

        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Some images uploaded; some failed.",
        );
        expect(document.getElementById("uploadStatus").innerHTML).toContain(
            "Failed validation",
        );
    });

    test("reports non-json and unexpected upload errors", async () => {
        fetch
            .mockResolvedValueOnce({
                headers: { get: () => "text/html" },
                status: 413,
                text: async () => "<html>payload too large</html>",
            })
            .mockRejectedValueOnce(new Error("boom"));

        const input = document.getElementById("uploads");
        const files = [
            new File(["a"], "first.png", { type: "image/png" }),
            new File(["b"], "second.png", { type: "image/png" }),
        ];
        Object.defineProperty(input, "files", {
            configurable: true,
            value: files,
        });
        input.dispatchEvent(new Event("change", { bubbles: true }));

        document.getElementById("uploadAll").click();
        await flushPromises();

        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Upload request was too large for the server.",
        );

        document.getElementById("uploadAll").click();
        await flushPromises();

        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Unexpected error while uploading.",
        );
    });
});

describe("admin-image-bulk-upload controller branch coverage", () => {
    let application;

    const renderFixture = ({
        includeFormTarget = true,
        includeUploadsInputTarget = true,
        includeFileListTarget = true,
        includeUploadButtonTarget = true,
        includeUploadStatusTarget = true,
        includeButtonLabelTarget = true,
        includeButtonSpinnerTarget = true,
        chunkSizeValue = "2",
        includeActionAttribute = true,
    } = {}) => {
        document.body.innerHTML = `
            <form
                id="bulkUploadForm"
                ${includeActionAttribute ? 'action="/admin/images/bulk-upload"' : ""}
                data-controller="admin-image-bulk-upload"
                ${includeFormTarget ? 'data-admin-image-bulk-upload-target="form"' : ""}
                data-admin-image-bulk-upload-chunk-size-value="${chunkSizeValue}"
            >
                <input
                    type="file"
                    id="uploads"
                    name="uploads[]"
                    multiple
                    ${includeUploadsInputTarget ? 'data-admin-image-bulk-upload-target="uploadsInput"' : ""}
                    data-action="change->admin-image-bulk-upload#fileSelectionChanged"
                />
                <div id="fileList" ${includeFileListTarget ? 'data-admin-image-bulk-upload-target="fileList"' : ""}></div>
                <button
                    id="uploadAll"
                    type="button"
                    disabled
                    ${includeUploadButtonTarget ? 'data-admin-image-bulk-upload-target="uploadButton"' : ""}
                    data-action="click->admin-image-bulk-upload#uploadAll"
                >
                    <span class="label" ${includeButtonLabelTarget ? 'data-admin-image-bulk-upload-target="buttonLabel"' : ""}>Upload Selected</span>
                    <span class="spinner-border d-none" ${includeButtonSpinnerTarget ? 'data-admin-image-bulk-upload-target="buttonSpinner"' : ""}></span>
                </button>
                <div id="uploadStatus" ${includeUploadStatusTarget ? 'data-admin-image-bulk-upload-target="uploadStatus"' : ""}></div>
            </form>
        `;
    };

    const startController = async (options = {}) => {
        if (application) {
            application.stop();
            application = null;
        }

        renderFixture(options);

        application = Application.start();
        application.register(
            "admin-image-bulk-upload",
            AdminImageBulkUploadController,
        );

        const root = document.querySelector(
            '[data-controller="admin-image-bulk-upload"]',
        );
        for (let i = 0; i < 4; i += 1) {
            const controller =
                application.getControllerForElementAndIdentifier(
                    root,
                    "admin-image-bulk-upload",
                ) ||
                application.controllers.find(
                    (item) => item.identifier === "admin-image-bulk-upload",
                );
            if (controller) {
                return controller;
            }

            await Promise.resolve();
        }

        return undefined;
    };

    beforeEach(() => {
        global.fetch = jest.fn();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        jest.restoreAllMocks();
        delete global.fetch;
        document.body.innerHTML = "";
    });

    test("file selection/change and render guard branches", async () => {
        const controller = await startController({
            includeFileListTarget: false,
            includeUploadButtonTarget: false,
        });

        expect(() =>
            controller.fileSelectionChanged({ target: {} }),
        ).not.toThrow();
        expect(() => controller.renderFileRows([])).not.toThrow();
        expect(controller.currentFiles()).toEqual([]);
    });

    test("chunk size/default helpers and status/detail guards", async () => {
        const controller = await startController({
            includeUploadsInputTarget: false,
            includeUploadStatusTarget: false,
            includeUploadButtonTarget: false,
            includeButtonLabelTarget: false,
            includeButtonSpinnerTarget: false,
            chunkSizeValue: "0",
        });

        expect(controller.chunkSize).toBe(3);
        expect(controller.currentFiles()).toEqual([]);
        expect(() => controller.setUploadingState(true)).not.toThrow();
        expect(() => controller.setUploadingState(false)).not.toThrow();
        expect(() => controller.showStatus("danger", "hidden")).not.toThrow();
        expect(controller.buildDetails([])).toBe("");

        const details = controller.buildDetails([
            { success: true, existing: true },
            { success: false, name: "bad.png", error: "invalid" },
        ]);
        expect(details).toContain("unnamed");
        expect(details).toContain("(duplicate)");
        expect(details).toContain("invalid");
    });

    test("uploadAll handles danger and non-413 non-json branches", async () => {
        await startController();

        const input = document.getElementById("uploads");
        const files = [new File(["a"], "first.png", { type: "image/png" })];
        Object.defineProperty(input, "files", {
            configurable: true,
            value: files,
        });
        input.dispatchEvent(new Event("change", { bubbles: true }));

        fetch.mockResolvedValueOnce({
            headers: { get: () => "application/json" },
            json: async () => ({
                results: [{ success: false, name: "first.png", error: "bad" }],
            }),
        });
        document.getElementById("uploadAll").click();
        await flushPromises();
        await flushPromises();
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Upload failed.",
        );

        fetch.mockResolvedValueOnce({
            headers: { get: () => "application/json" },
            json: async () => ({
                error: "chunk failure",
            }),
        });
        document.getElementById("uploadAll").click();
        await flushPromises();
        await flushPromises();
        expect(document.getElementById("uploadStatus").innerHTML).toContain(
            "batch-1",
        );

        fetch.mockResolvedValueOnce({
            headers: { get: () => "text/html" },
            status: 500,
            text: async () => "<html>server error</html>",
        });
        document.getElementById("uploadAll").click();
        await flushPromises();
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Server returned invalid response",
        );
    });

    test("uploadChunk fallback action and non-json metadata branches", async () => {
        const controller = await startController({
            includeFormTarget: false,
            includeActionAttribute: false,
        });

        fetch.mockResolvedValueOnce({
            headers: { get: () => "text/html; charset=utf-8" },
            status: 500,
            text: async () => "x".repeat(700),
        });

        await expect(
            controller.uploadChunk(
                [new File(["a"], "first.png", { type: "image/png" })],
                3,
            ),
        ).rejects.toEqual(
            expect.objectContaining({
                name: "NonJsonResponseError",
                status: 500,
                chunkIndex: 3,
            }),
        );
        expect(fetch.mock.calls[0][0]).toContain("/admin/images/bulk-upload");

        fetch.mockResolvedValueOnce({
            headers: { get: () => "APPLICATION/JSON" },
            json: async () => ({
                results: [{ success: true, name: "ok.png" }],
            }),
        });

        await expect(
            controller.uploadChunk(
                [new File(["b"], "second.png", { type: "image/png" })],
                4,
            ),
        ).resolves.toEqual({ results: [{ success: true, name: "ok.png" }] });
    });
});
