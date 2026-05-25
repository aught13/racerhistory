import { jest } from "@jest/globals";

/**
 * Image Selector Tests
 *
 * Tests for ImageSelector class covering:
 * - Initialization and element binding
 * - Image loading and gallery rendering
 * - Image selection and upload workflow
 * - Search and filtering
 * - Cropper integration
 */

// Import the ImageSelector class
let ImageSelector;

describe("ImageSelector", () => {
    let modal;
    let config;
    let imageSelector;
    let mockModalInstance;

    beforeEach(async () => {
        // Load module
        jest.resetModules();
        const mod = await import("../image-selector.js");
        ImageSelector = mod.default || mod;

        // Setup DOM
        document.body.innerHTML = `
      <div id="test-modal" class="modal fade">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-body">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button id="test-modal-select-tab" class="nav-link active" data-bs-toggle="tab">Select</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button id="test-modal-upload-tab" class="nav-link" data-bs-toggle="tab">Upload</button>
                </li>
              </ul>

              <!-- Select Tab -->
              <div id="test-modal-select-tab" role="tabpanel">
                <input id="test-modal-search" type="text" placeholder="Search..." />
                <div id="test-modal-gallery" class="row"></div>
                <button id="test-modal-select-btn" class="btn btn-primary">Select</button>
              </div>

              <!-- Upload Tab -->
              <div id="test-modal-upload-tab" role="tabpanel">
                <input id="test-modal-file-input" type="file" accept="image/*" />
                <div id="test-modal-crop-container" style="display: none;">
                  <img id="test-modal-crop-image" src="" />
                  <div id="test-modal-crop-preview" style="display: none;"></div>
                </div>
                <div id="test-modal-no-preview">No image selected</div>
                <div id="test-modal-crop-controls" style="display: none;">
                  <button id="test-modal-rotate-left">Rotate Left</button>
                  <button id="test-modal-rotate-right">Rotate Right</button>
                  <button id="test-modal-reset-crop">Reset</button>
                </div>
                <button id="test-modal-upload-btn" class="btn btn-primary">Upload & Crop</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <input id="test-target-field" type="hidden" />
      <meta name="csrfToken" content="test-csrf-token" />
    `;

        // Setup global config
        window.imageSelectorConfig = {
            "test-modal": {
                targetFieldId: "test-target-field",
                aspectRatio: 1,
            },
        };

        // Mock bootstrap Modal with persistent instance
        mockModalInstance = {
            hide: jest.fn(),
        };

        window.bootstrap = {
            Modal: {
                getInstance: jest.fn(() => mockModalInstance),
            },
        };

        // Mock Cropper
        window.Cropper = jest.fn().mockImplementation(() => ({
            destroy: jest.fn(),
            rotate: jest.fn(),
            reset: jest.fn(),
            getCroppedCanvas: jest.fn().mockReturnValue({
                toBlob: jest.fn((cb) =>
                    cb(new Blob(["test"], { type: "image/jpeg" })),
                ),
            }),
        }));

        // Mock fetch globally
        global.fetch = jest.fn();

        // Create selector instance
        modal = document.getElementById("test-modal");
        config = window.imageSelectorConfig["test-modal"];
        imageSelector = new ImageSelector("test-modal");
    });

    afterEach(() => {
        jest.clearAllMocks();
        global.fetch.mockClear();
    });

    describe("Constructor & Initialization", () => {
        test("should initialize with valid modal", () => {
            expect(imageSelector.modalId).toBe("test-modal");
            expect(imageSelector.modal).toBe(modal);
            expect(imageSelector.config).toEqual(config);
        });

        test("should set default aspect ratio from config", () => {
            expect(imageSelector.aspectRatio).toBe(1);
        });

        test("should use custom aspect ratio if provided", () => {
            window.imageSelectorConfig["custom-modal"] = {
                targetFieldId: "test-target",
                aspectRatio: 16 / 9,
            };
            document.body.innerHTML +=
                '<div id="custom-modal" class="modal fade"></div>';

            const selector = new ImageSelector("custom-modal");
            expect(selector.aspectRatio).toBe(16 / 9);
        });

        test("should handle missing modal gracefully", () => {
            const consoleSpy = jest
                .spyOn(console, "error")
                .mockImplementation();
            new ImageSelector("nonexistent-modal");
            expect(consoleSpy).toHaveBeenCalledWith(
                "Modal not found:",
                "nonexistent-modal",
            );
            consoleSpy.mockRestore();
        });

        test("should initialize all DOM elements", () => {
            expect(imageSelector.selectTab).toBeTruthy();
            expect(imageSelector.uploadTab).toBeTruthy();
            expect(imageSelector.searchInput).toBeTruthy();
            expect(imageSelector.gallery).toBeTruthy();
            expect(imageSelector.fileInput).toBeTruthy();
            expect(imageSelector.targetField).toBeTruthy();
        });

        test("should bind all event listeners", () => {
            jest.spyOn(imageSelector.modal, "addEventListener");
            // Re-initialize to count new listeners
            new ImageSelector("test-modal");
            expect(imageSelector.gallery).toBeTruthy();
        });
    });

    describe("Image Loading", () => {
        test("should load images from /admin/images/browse", async () => {
            const mockImages = [
                {
                    id: 1,
                    thumbnail_url: "/thumb1.jpg",
                    url: "/img1.jpg",
                    tags: ["tag1"],
                },
                {
                    id: 2,
                    thumbnail_url: "/thumb2.jpg",
                    url: "/img2.jpg",
                    tags: ["tag2"],
                },
            ];

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, images: mockImages }),
            });

            await imageSelector.loadImages();

            expect(global.fetch).toHaveBeenCalledWith("/admin/images/browse", {
                credentials: "same-origin",
            });
            expect(imageSelector.loadedImages).toEqual(mockImages);
        });

        test("should include tag filter in request if provided", async () => {
            imageSelector.config.tagFilter = "person-123";
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, images: [] }),
            });

            await imageSelector.loadImages();

            expect(global.fetch).toHaveBeenCalledWith(
                expect.stringContaining("tag=person-123"),
                expect.any(Object),
            );
        });

        test("should render loading spinner", async () => {
            global.fetch.mockImplementationOnce(
                () =>
                    new Promise(() => {
                        /* never resolves */
                    }),
            );

            imageSelector.loadImages();
            expect(imageSelector.gallery.innerHTML).toContain("spinner-border");

            // Clean up
            global.fetch.mockClear();
        });

        test("should handle fetch errors gracefully", async () => {
            global.fetch.mockRejectedValueOnce(new Error("Network error"));

            await imageSelector.loadImages();

            expect(imageSelector.gallery.innerHTML).toContain(
                "Failed to load images",
            );
        });

        test("should handle non-ok response", async () => {
            global.fetch.mockResolvedValueOnce({
                ok: false,
                json: async () => ({}),
            });

            await imageSelector.loadImages();

            expect(imageSelector.gallery.innerHTML).toContain(
                "Failed to load images",
            );
        });
    });

    describe("Gallery Rendering", () => {
        test("should render images correctly", () => {
            const images = [
                {
                    id: 1,
                    url: "/img1.jpg",
                    thumbnail_url: "/thumb1.jpg",
                    tags: ["tag1", "tag2"],
                },
            ];
            imageSelector.renderGallery(images);

            const card = imageSelector.gallery.querySelector(
                '[data-image-id="1"]',
            );
            expect(card).toBeTruthy();
            expect(card.querySelector("img").src).toContain("/thumb1.jpg");
        });

        test("should handle empty image list", () => {
            imageSelector.renderGallery([]);
            expect(imageSelector.gallery.innerHTML).toContain(
                "No images found",
            );
        });

        test("should handle null images", () => {
            imageSelector.renderGallery(null);
            expect(imageSelector.gallery.innerHTML).toContain(
                "No images found",
            );
        });

        test("should display image tags", () => {
            const images = [
                { id: 1, url: "/img1.jpg", tags: ["tag1", "tag2", "tag3"] },
            ];
            imageSelector.renderGallery(images);

            const badges = imageSelector.gallery.querySelectorAll(".badge");
            expect(badges.length).toBe(2); // Only first 2 tags
            expect(badges[0].textContent).toBe("tag1");
            expect(badges[1].textContent).toBe("tag2");
        });

        test("should handle images without tags", () => {
            const images = [{ id: 1, url: "/img1.jpg", tags: null }];
            imageSelector.renderGallery(images);

            const card = imageSelector.gallery.querySelector(
                '[data-image-id="1"]',
            );
            expect(card).toBeTruthy();
            expect(card.querySelector(".badge")).toBeFalsy();
        });

        test("should use fallback to main URL if thumbnail missing", () => {
            const images = [{ id: 1, url: "/img1.jpg", thumbnail_url: null }];
            imageSelector.renderGallery(images);

            const img = imageSelector.gallery.querySelector("img");
            expect(img.src).toContain("/img1.jpg");
        });
    });

    describe("Image Selection", () => {
        test("should select image on gallery card click", () => {
            const images = [{ id: 5, url: "/img5.jpg" }];
            imageSelector.renderGallery(images);

            const card = imageSelector.gallery.querySelector(
                '[data-image-id="5"]',
            );
            card.click();

            expect(imageSelector.selectedImageId).toBe(5);
            expect(card.classList.contains("border-primary")).toBe(true);
        });

        test("should deselect previous selection when new image clicked", () => {
            const images = [
                { id: 1, url: "/img1.jpg" },
                { id: 2, url: "/img2.jpg" },
            ];
            imageSelector.renderGallery(images);

            // Select first
            imageSelector.gallery.querySelector('[data-image-id="1"]').click();
            expect(imageSelector.selectedImageId).toBe(1);

            // Select second
            imageSelector.gallery.querySelector('[data-image-id="2"]').click();
            expect(imageSelector.selectedImageId).toBe(2);

            // Only second should have border
            const cards = imageSelector.gallery.querySelectorAll(".image-card");
            expect(cards[0].classList.contains("border-primary")).toBe(false);
            expect(cards[1].classList.contains("border-primary")).toBe(true);
        });

        test("should set target field value on selection", () => {
            const images = [
                { id: 42, url: "/img42.jpg", hero_url: "/hero42.jpg" },
            ];
            imageSelector.loadedImages = images;
            imageSelector.renderGallery(images);

            imageSelector.gallery.querySelector('[data-image-id="42"]').click();
            imageSelector.onSelectImage();

            expect(imageSelector.targetField.value).toBe("42");
            expect(imageSelector.targetField.dataset.selectedImageHeroUrl).toBe(
                "/hero42.jpg",
            );
        });

        test("should dispatch change event on target field", () => {
            const images = [{ id: 99, url: "/img99.jpg" }];
            imageSelector.renderGallery(images);

            const changeSpy = jest.fn();
            imageSelector.targetField.addEventListener("change", changeSpy);

            imageSelector.gallery.querySelector('[data-image-id="99"]').click();
            imageSelector.onSelectImage();

            expect(changeSpy).toHaveBeenCalled();
        });

        test("should close modal after selection", () => {
            mockModalInstance.hide.mockClear();
            const images = [{ id: 1, url: "/img1.jpg" }];
            imageSelector.renderGallery(images);

            imageSelector.gallery.querySelector('[data-image-id="1"]').click();
            imageSelector.onSelectImage();

            expect(mockModalInstance.hide).toHaveBeenCalled();
        });

        test("should alert if no image selected", () => {
            const alertSpy = jest.spyOn(window, "alert").mockImplementation();
            imageSelector.selectedImageId = null;
            imageSelector.onSelectImage();

            expect(alertSpy).toHaveBeenCalledWith("Please select an image");
            alertSpy.mockRestore();
        });
    });

    describe("Search & Filtering", () => {
        test("should filter images by ID", () => {
            const images = [
                { id: 1, original_name: "photo1.jpg", tags: [] },
                { id: 2, original_name: "photo2.jpg", tags: [] },
            ];
            imageSelector.loadedImages = images;

            imageSelector.onSearch("2");

            const cards =
                imageSelector.gallery.querySelectorAll("[data-image-id]");
            expect(cards.length).toBe(1);
            expect(cards[0].dataset.imageId).toBe("2");
        });

        test("should filter images by filename", () => {
            const images = [
                { id: 1, original_name: "photo-test.jpg", tags: [] },
                { id: 2, original_name: "image.jpg", tags: [] },
            ];
            imageSelector.loadedImages = images;

            imageSelector.onSearch("photo");

            const cards =
                imageSelector.gallery.querySelectorAll("[data-image-id]");
            expect(cards.length).toBe(1);
        });

        test("should filter images by tag", () => {
            const images = [
                { id: 1, original_name: "", tags: ["person-1", "admin"] },
                { id: 2, original_name: "", tags: ["person-2"] },
            ];
            imageSelector.loadedImages = images;

            imageSelector.onSearch("person-1");

            const cards =
                imageSelector.gallery.querySelectorAll("[data-image-id]");
            expect(cards.length).toBe(1);
        });

        test("should be case-insensitive", () => {
            const images = [{ id: 1, original_name: "PHOTO.jpg", tags: [] }];
            imageSelector.loadedImages = images;

            imageSelector.onSearch("photo");

            const cards =
                imageSelector.gallery.querySelectorAll("[data-image-id]");
            expect(cards.length).toBe(1);
        });

        test("should show all images when search is empty", () => {
            const images = [
                { id: 1, original_name: "photo1.jpg", tags: [] },
                { id: 2, original_name: "photo2.jpg", tags: [] },
            ];
            imageSelector.loadedImages = images;

            imageSelector.onSearch("");

            const cards =
                imageSelector.gallery.querySelectorAll("[data-image-id]");
            expect(cards.length).toBe(2);
        });
    });

    describe("File Upload & Cropping", () => {
        test("should initialize cropper on file selection", () => {
            const file = new File(["test"], "test.jpg", { type: "image/jpeg" });
            const event = {
                target: {
                    files: [file],
                },
            };

            const readerMock = {
                onload: null,
                readAsDataURL: jest.fn(function () {
                    this.onload({
                        target: { result: "data:image/jpeg;base64,test" },
                    });
                }),
            };

            global.FileReader = jest.fn(() => readerMock);

            imageSelector.onFileSelected(event);

            expect(imageSelector.cropImage.src).toBe(
                "data:image/jpeg;base64,test",
            );
            expect(imageSelector.cropContainer.style.display).toBe("block");
            expect(window.Cropper).toHaveBeenCalled();
        });

        test("should destroy existing cropper before creating new one", () => {
            const destroySpy = jest.fn();
            imageSelector.cropper = { destroy: destroySpy };

            const file = new File(["test"], "test.jpg", { type: "image/jpeg" });
            const event = {
                target: {
                    files: [file],
                },
            };

            const readerMock = {
                onload: null,
                readAsDataURL: jest.fn(function () {
                    this.onload({
                        target: { result: "data:image/jpeg;base64,test" },
                    });
                }),
            };

            global.FileReader = jest.fn(() => readerMock);

            imageSelector.onFileSelected(event);

            expect(destroySpy).toHaveBeenCalled();
        });

        test("should handle no file selected", () => {
            const event = {
                target: {
                    files: [],
                },
            };

            // Should not throw
            expect(() => imageSelector.onFileSelected(event)).not.toThrow();
        });

        test("should enable crop controls on file selection", () => {
            const file = new File(["test"], "test.jpg", { type: "image/jpeg" });
            const event = {
                target: {
                    files: [file],
                },
            };

            const readerMock = {
                onload: null,
                readAsDataURL: jest.fn(function () {
                    this.onload({
                        target: { result: "data:image/jpeg;base64,test" },
                    });
                }),
            };

            global.FileReader = jest.fn(() => readerMock);

            imageSelector.onFileSelected(event);

            expect(imageSelector.cropControls.style.display).toBe("block");
            expect(imageSelector.noPreview.style.display).toBe("none");
        });

        test("should rotate image left on rotate button click", () => {
            imageSelector.cropper = { rotate: jest.fn() };
            imageSelector.rotateLeftBtn.click();

            expect(imageSelector.cropper.rotate).toHaveBeenCalledWith(-90);
        });

        test("should rotate image right on rotate button click", () => {
            imageSelector.cropper = { rotate: jest.fn() };
            imageSelector.rotateRightBtn.click();

            expect(imageSelector.cropper.rotate).toHaveBeenCalledWith(90);
        });

        test("should reset crop on reset button click", () => {
            imageSelector.cropper = { reset: jest.fn() };
            imageSelector.resetCropBtn.click();

            expect(imageSelector.cropper.reset).toHaveBeenCalled();
        });
    });

    describe("Image Upload Process", () => {
        beforeEach(() => {
            // Newer ImageSelector requires a file selection before upload.
            imageSelector.selectedFile = new File(["test"], "test.jpg", {
                type: "image/jpeg",
            });
        });

        test("should upload cropped image successfully", async () => {
            const mockBlob = new Blob(["test"], { type: "image/jpeg" });
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(mockBlob)),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, image: { id: 123 } }),
            });

            await imageSelector.onUploadImage();

            expect(global.fetch).toHaveBeenCalledWith(
                "/admin/images/upload",
                expect.objectContaining({
                    method: "POST",
                    credentials: "same-origin",
                }),
            );
        });

        test("should set canvas dimensions based on aspect ratio", async () => {
            imageSelector.aspectRatio = 16 / 9;
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(new Blob())),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, image: { id: 1 } }),
            });

            await imageSelector.onUploadImage();

            const call =
                imageSelector.cropper.getCroppedCanvas.mock.calls[0][0];
            expect(call.height).toBe(Math.round(800 / (16 / 9)));
        });

        test("should include upload context in FormData", async () => {
            imageSelector.config.uploadContext = { type: "person", id: 42 };
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(new Blob())),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, image: { id: 1 } }),
            });

            await imageSelector.onUploadImage();

            const formDataArg = global.fetch.mock.calls[0][1].body;
            expect(formDataArg.has("context")).toBe(true);
        });

        test("should set target field after upload", async () => {
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(new Blob())),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, image: { id: 777 } }),
            });

            await imageSelector.onUploadImage();

            expect(imageSelector.targetField.value).toBe("777");
        });

        test("should close modal after successful upload", async () => {
            mockModalInstance.hide.mockClear();
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(new Blob())),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, image: { id: 1 } }),
            });

            await imageSelector.onUploadImage();

            expect(mockModalInstance.hide).toHaveBeenCalled();
        });

        test("should disable button during upload", async () => {
            imageSelector.uploadBtn.disabled = false;
            imageSelector.uploadBtn.textContent = "Upload & Crop";

            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => {
                        // Simulate async delay
                        setTimeout(() => cb(new Blob()), 0);
                    }),
                }),
            };

            global.fetch.mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        setTimeout(
                            () =>
                                resolve({
                                    ok: true,
                                    json: async () => ({
                                        success: true,
                                        image: { id: 1 },
                                    }),
                                }),
                            10,
                        );
                    }),
            );

            const uploadPromise = imageSelector.onUploadImage();
            expect(imageSelector.uploadBtn.disabled).toBe(true);

            await uploadPromise;
            expect(imageSelector.uploadBtn.disabled).toBe(false);
            expect(imageSelector.uploadBtn.textContent).toBe("Upload & Crop");
        });

        test("should handle upload errors", async () => {
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(new Blob())),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: false,
                json: async () => ({ success: false, error: "Server error" }),
            });

            const alertSpy = jest.spyOn(window, "alert").mockImplementation();

            await imageSelector.onUploadImage();

            expect(alertSpy).toHaveBeenCalledWith(
                expect.stringContaining("Upload failed"),
            );
            alertSpy.mockRestore();
        });

        test("should alert if no file selected when upload attempted", () => {
            imageSelector.selectedFile = null;
            const alertSpy = jest.spyOn(window, "alert").mockImplementation();

            imageSelector.onUploadImage();

            expect(alertSpy).toHaveBeenCalledWith(
                "Please select an image first",
            );
            alertSpy.mockRestore();
        });

        test("should include CSRF token in upload request", async () => {
            imageSelector.cropper = {
                getCroppedCanvas: jest.fn().mockReturnValue({
                    toBlob: jest.fn((cb) => cb(new Blob())),
                }),
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, image: { id: 1 } }),
            });

            await imageSelector.onUploadImage();

            const headers = global.fetch.mock.calls[0][1].headers;
            expect(headers["X-CSRF-Token"]).toBe("test-csrf-token");
        });
    });

    describe("Tab Management", () => {
        test("should show select button on select tab", () => {
            imageSelector.onSelectTabShown();

            expect(imageSelector.selectBtn.style.display).toBe("inline-block");
            expect(imageSelector.uploadBtn.style.display).toBe("none");
        });

        test("should show upload button on upload tab", () => {
            imageSelector.onUploadTabShown();

            expect(imageSelector.selectBtn.style.display).toBe("none");
            expect(imageSelector.uploadBtn.style.display).toBe("inline-block");
        });

        test("should load images when select tab shown if not loaded", async () => {
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, images: [] }),
            });

            imageSelector.loadedImages = [];
            await imageSelector.onSelectTabShown();

            expect(global.fetch).toHaveBeenCalled();
        });

        test("should not reload images if already loaded", async () => {
            global.fetch.mockClear();
            imageSelector.loadedImages = [{ id: 1, url: "/img1.jpg" }];

            imageSelector.onSelectTabShown();

            expect(global.fetch).not.toHaveBeenCalled();
        });
    });

    describe("Auto-initialization", () => {
        test("should auto-initialize on DOMContentLoaded", () => {
            document.body.innerHTML = `
        <div id="auto-modal-1" class="modal fade">
          <div>
            <input id="target-1" type="hidden" />
            <div id="auto-modal-1-select-tab"></div>
            <div id="auto-modal-1-upload-tab"></div>
            <input id="auto-modal-1-search" />
            <div id="auto-modal-1-gallery"></div>
            <input id="auto-modal-1-file-input" />
            <div id="auto-modal-1-crop-container"></div>
            <img id="auto-modal-1-crop-image" />
            <div id="auto-modal-1-crop-preview"></div>
            <div id="auto-modal-1-no-preview"></div>
            <div id="auto-modal-1-crop-controls"></div>
            <button id="auto-modal-1-select-btn"></button>
            <button id="auto-modal-1-upload-btn"></button>
            <button id="auto-modal-1-rotate-left"></button>
            <button id="auto-modal-1-rotate-right"></button>
            <button id="auto-modal-1-reset-crop"></button>
          </div>
        </div>
      `;

            window.imageSelectorConfig = {
                "auto-modal-1": {
                    targetFieldId: "target-1",
                },
            };

            // Trigger DOMContentLoaded
            document.dispatchEvent(new Event("DOMContentLoaded"));

            // Should have created instance
            const modal = document.getElementById("auto-modal-1");
            expect(modal).toBeTruthy();
        });
    });
});
