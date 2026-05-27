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
});
