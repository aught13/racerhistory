import { jest } from "@jest/globals";

/* image-selector.crop.branches.test.js
 * Tests exercising crop and upload branches for image-selector.js
 */
// ...existing code...

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    global.alert = jest.fn();
    // minimal bootstrap stub
    global.bootstrap = { Modal: { getInstance: () => ({ hide: jest.fn() }) } };
});

test("onFileSelected initializes Cropper and displays UI", async () => {
    // prepare DOM
    const modalId = "crop-modal";
    const modal = document.createElement("div");
    modal.id = modalId;
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.id = `${modalId}-file-input`;
    const cropContainer = document.createElement("div");
    cropContainer.id = `${modalId}-crop-container`;
    const cropImage = document.createElement("img");
    cropImage.id = `${modalId}-crop-image`;
    const cropPreview = document.createElement("div");
    cropPreview.id = `${modalId}-crop-preview`;
    const noPreview = document.createElement("div");
    noPreview.id = `${modalId}-no-preview`;
    const cropControls = document.createElement("div");
    cropControls.id = `${modalId}-crop-controls`;
    const uploadBtn = document.createElement("button");
    uploadBtn.id = `${modalId}-upload-btn`;
    document.body.appendChild(modal);
    document.body.appendChild(fileInput);
    document.body.appendChild(cropContainer);
    document.body.appendChild(cropImage);
    document.body.appendChild(cropPreview);
    document.body.appendChild(noPreview);
    document.body.appendChild(cropControls);
    document.body.appendChild(uploadBtn);
    // Removed unused variable 'onloadFns' for ESLint

    class MockReader {
        constructor() {
            this.onload = null;
        }
        readAsDataURL() {
            if (this.onload) this.onload({ target: { result: "data:img" } });
        }
    }
    global.FileReader = MockReader;

    // Mock Cropper constructor to expose methods used
    const cropperMock = {
        destroy: jest.fn(),
    };
    global.Cropper = jest.fn().mockImplementation(() => cropperMock);

    const _ismod = await import("../../legacy/image-selector.js");
    const ImageSelector = _ismod.default || _ismod;
    const inst = new ImageSelector(modalId);

    // simulate file selection event
    const fakeFile = new Blob(["x"], { type: "image/jpeg" });
    const ev = { target: { files: [fakeFile] } };
    inst.onFileSelected(ev);

    // cropContainer should be visible and cropper initialized
    expect(cropContainer.style.display).toBe("block");
    expect(cropPreview.style.display).toBe("block");
    expect(noPreview.style.display).toBe("none");
    expect(global.Cropper).toHaveBeenCalled();
});

test("onUploadImage handles crop path and upload failure/success", async () => {
    const modalId = "crop-modal-2";
    const modal = document.createElement("div");
    modal.id = modalId;
    const uploadBtn = document.createElement("button");
    uploadBtn.id = `${modalId}-upload-btn`;
    document.body.appendChild(modal);
    document.body.appendChild(uploadBtn);
    const target = document.createElement("input");
    target.id = "targetCrop2";
    document.body.appendChild(target);

    window.imageSelectorConfig = {
        [modalId]: { targetFieldId: "targetCrop2" },
    };

    // create a fake cropper returning a canvas with toBlob
    const toBlobFn = jest.fn((cb) =>
        cb(new Blob(["b"], { type: "image/jpeg" })),
    );
    const canvas = { toBlob: toBlobFn };
    const cropperObj = { getCroppedCanvas: jest.fn(() => canvas) };

    // Mock Cropper to return our cropperObj
    global.Cropper = jest.fn().mockImplementation(() => cropperObj);

    // Mock File selected
    const fakeFile = new Blob(["x"], { type: "image/jpeg" });

    const _ismod = await import("../../legacy/image-selector.js");
    const ImageSelector = _ismod.default || _ismod;
    const inst = new ImageSelector(modalId);
    inst.uploadBtn = uploadBtn;
    inst.selectedFile = fakeFile;
    inst.cropper = cropperObj;
    inst.skipCropToggle = { checked: false };

    // mock csrf meta
    const meta = document.createElement("meta");
    meta.name = "csrfToken";
    meta.content = "token";
    document.head.appendChild(meta);

    // Case 1: upload network failure (response.ok false)
    global.fetch = jest.fn().mockResolvedValue({ ok: false });
    await inst.onUploadImage();
    expect(global.fetch).toHaveBeenCalled();
    expect(uploadBtn.disabled).toBe(false);

    // Case 2: upload success but server returns success=false
    global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ success: false, error: "bad" }),
    });
    await inst.onUploadImage();
    // should have alerted
    expect(global.alert).toHaveBeenCalled();

    // Case 3: upload success and returns image id
    global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ success: true, image: { id: 1234 } }),
    });
    await inst.onUploadImage();
    expect(document.getElementById("targetCrop2").value).toBe("1234");
});
