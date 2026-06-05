/**
 * Tests for TinyMCE Bootstrap Configuration Module
 *
 * @jest-environment jsdom
 */

import {
    jest,
    describe,
    test,
    expect,
    beforeEach,
    afterEach,
} from "@jest/globals";

describe("tinymce-bootstrap-config module", () => {
    let originalTinymce;

    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        document.head.innerHTML = "";

        // Store original tinymce if exists
        originalTinymce = window.tinymce;
    });

    afterEach(() => {
        // Restore original tinymce
        if (originalTinymce !== undefined) {
            window.tinymce = originalTinymce;
        } else {
            delete window.tinymce;
        }
        jest.restoreAllMocks();
    });

    test("createTinyMCEConfig returns valid configuration object", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig({
            selector: "#test-editor",
        });

        expect(config).toBeDefined();
        expect(config.selector).toBe("#test-editor");
        expect(config.license_key).toBe("gpl");
        expect(config.plugins).toContain("image");
        expect(config.plugins).toContain("table");
        expect(config.plugins).toContain("link");
        expect(config.content_css).toBeDefined();
        expect(Array.isArray(config.content_css)).toBe(true);
    });

    test("createTinyMCEConfig uses default selector when not provided", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig();

        expect(config.selector).toBe("#body-editor");
    });

    test("createTinyMCEConfig includes image upload configuration", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig({
            uploadUrl: "/custom/upload",
        });

        expect(config.images_upload_url).toBe("/custom/upload");
        expect(config.automatic_uploads).toBe(true);
        expect(config.images_upload_credentials).toBe(true);
        expect(typeof config.images_upload_handler).toBe("function");
    });

    test("createTinyMCEConfig includes table configuration", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig();

        expect(config.table_default_styles).toBeDefined();
        expect(config.table_class_list).toBeDefined();
        expect(Array.isArray(config.table_class_list)).toBe(true);
        expect(config.table_class_list.length).toBeGreaterThan(0);
    });

    test("createTinyMCEConfig includes style formats for Bootstrap", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig();

        expect(config.style_formats).toBeDefined();
        expect(Array.isArray(config.style_formats)).toBe(true);

        // Check for image position styles
        const imageStyles = config.style_formats.find(
            (f) => f.title === "Image Position",
        );
        expect(imageStyles).toBeDefined();
        expect(imageStyles.items.length).toBeGreaterThan(0);
    });

    test("createTinyMCEConfig accepts custom minHeight", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig({
            minHeight: 600,
        });

        expect(config.min_height).toBe(600);
    });

    test("createTinyMCEConfig enables menubar when specified", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const configWithMenu = mod.createTinyMCEConfig({ menubar: true });
        const configWithoutMenu = mod.createTinyMCEConfig({ menubar: false });

        expect(configWithMenu.menubar).toBe(true);
        expect(configWithoutMenu.menubar).toBe(false);
    });

    test("initTinyMCE rejects when TinyMCE is not loaded", async () => {
        delete window.tinymce;

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        await expect(mod.initTinyMCE()).rejects.toThrow(
            "TinyMCE is not loaded",
        );
    });

    test("initTinyMCE calls tinymce.init with config", async () => {
        const mockInit = jest.fn();
        window.tinymce = { init: mockInit };

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        mod.initTinyMCE({ selector: "#my-editor" });

        expect(mockInit).toHaveBeenCalledTimes(1);
        const passedConfig = mockInit.mock.calls[0][0];
        expect(passedConfig.selector).toBe("#my-editor");
    });

    test("insertResponsiveImage does nothing without editor", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        // Should not throw
        expect(() =>
            mod.insertResponsiveImage(null, "/img/storage/test.jpg"),
        ).not.toThrow();
        expect(() =>
            mod.insertResponsiveImage(undefined, "/img/storage/test.jpg"),
        ).not.toThrow();
    });

    test("insertResponsiveImage does nothing without imageUrl", async () => {
        const mockEditor = {
            insertContent: jest.fn(),
        };

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        mod.insertResponsiveImage(mockEditor, null);
        mod.insertResponsiveImage(mockEditor, undefined);

        expect(mockEditor.insertContent).not.toHaveBeenCalled();
    });

    test("insertResponsiveImage inserts picture element with direct image URL", async () => {
        const mockEditor = {
            insertContent: jest.fn(),
        };

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        mod.insertResponsiveImage(
            mockEditor,
            "/img/storage/2026/05/inline.jpg",
            {
                alt: "Test Image",
                position: "center",
            },
        );

        expect(mockEditor.insertContent).toHaveBeenCalledTimes(1);
        const insertedHtml = mockEditor.insertContent.mock.calls[0][0];

        expect(insertedHtml).toContain("<picture");
        expect(insertedHtml).not.toContain("<source");
        expect(insertedHtml).toContain("/img/storage/2026/05/inline.jpg");
        expect(insertedHtml).toContain('alt="Test Image"');
        expect(insertedHtml).toContain("img-center");
    });

    test("insertResponsiveImage applies float classes based on position", async () => {
        const mockEditor = {
            insertContent: jest.fn(),
        };

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        // Test left position
        mod.insertResponsiveImage(mockEditor, "/img/storage/left.jpg", {
            position: "left",
        });
        expect(mockEditor.insertContent.mock.calls[0][0]).toContain(
            "img-float-left",
        );

        // Test right position
        mockEditor.insertContent.mockClear();
        mod.insertResponsiveImage(mockEditor, "/img/storage/right.jpg", {
            position: "right",
        });
        expect(mockEditor.insertContent.mock.calls[0][0]).toContain(
            "img-float-right",
        );

        // Test center position
        mockEditor.insertContent.mockClear();
        mod.insertResponsiveImage(mockEditor, "/img/storage/center.jpg", {
            position: "center",
        });
        expect(mockEditor.insertContent.mock.calls[0][0]).toContain(
            "img-center",
        );

        // Test inline (default) position
        mockEditor.insertContent.mockClear();
        mod.insertResponsiveImage(mockEditor, "/img/storage/inline.jpg", {
            position: "inline",
        });
        const inlineHtml = mockEditor.insertContent.mock.calls[0][0];
        expect(inlineHtml).not.toContain("img-float-left");
        expect(inlineHtml).not.toContain("img-float-right");
    });

    test("default export includes all functions", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        expect(mod.default).toBeDefined();
        expect(typeof mod.default.createTinyMCEConfig).toBe("function");
        expect(typeof mod.default.initTinyMCE).toBe("function");
        expect(typeof mod.default.insertResponsiveImage).toBe("function");
        expect(typeof mod.default.getCsrfToken).toBe("function");
    });

    test("getCsrfToken returns token from meta tag", async () => {
        document.head.innerHTML =
            '<meta name="csrfToken" content="test-csrf-token-123">';

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        expect(mod.default.getCsrfToken()).toBe("test-csrf-token-123");
    });

    test("getCsrfToken returns null when no meta tag", async () => {
        document.head.innerHTML = "";

        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        expect(mod.default.getCsrfToken()).toBeNull();
    });

    test("config includes extended_valid_elements for picture", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig();

        expect(config.extended_valid_elements).toContain("picture");
        expect(config.extended_valid_elements).toContain("source");
        expect(config.extended_valid_elements).toContain("srcset");
        expect(config.extended_valid_elements).toContain("loading");
    });

    test("config toolbar includes all essential buttons", async () => {
        const mod =
            await import("../../legacy/modules/tinymce-bootstrap-config.mjs");

        const config = mod.createTinyMCEConfig();
        const toolbarStr = Array.isArray(config.toolbar)
            ? config.toolbar.join(" ")
            : config.toolbar;

        expect(toolbarStr).toContain("image");
        expect(toolbarStr).toContain("table");
        expect(toolbarStr).toContain("link");
        expect(toolbarStr).toContain("code");
        expect(toolbarStr).toContain("fullscreen");
    });
});
