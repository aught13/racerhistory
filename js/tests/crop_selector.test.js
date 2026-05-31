/* global beforeEach, describe, expect, test */

import { jest as jestGlobals } from "@jest/globals";

let CropSelector;

const createContext = () => ({
    setTransform: jestGlobals.fn(),
    save: jestGlobals.fn(),
    translate: jestGlobals.fn(),
    rotate: jestGlobals.fn(),
    scale: jestGlobals.fn(),
    drawImage: jestGlobals.fn(),
    restore: jestGlobals.fn(),
    fillRect: jestGlobals.fn(),
    strokeRect: jestGlobals.fn(),
    beginPath: jestGlobals.fn(),
    moveTo: jestGlobals.fn(),
    lineTo: jestGlobals.fn(),
    stroke: jestGlobals.fn(),
    fillStyle: "",
    strokeStyle: "",
    lineWidth: 1,
    imageSmoothingEnabled: false,
    imageSmoothingQuality: "low",
});

const setupSelector = ({
    complete = true,
    naturalWidth = 200,
    naturalHeight = 200,
    clientWidth = 300,
    imageId = "crop-image",
    canvasId = "crop-canvas",
    withContainer = true,
    options = {},
} = {}) => {
    document.body.innerHTML = "";

    const canvas = document.createElement("canvas");
    canvas.id = canvasId;
    canvas.width = 200;
    canvas.height = 200;

    let container = null;
    if (withContainer) {
        container = document.createElement("div");
        Object.defineProperty(container, "clientWidth", {
            value: clientWidth,
            configurable: true,
        });
        container.appendChild(canvas);
        document.body.appendChild(container);
    } else {
        document.body.appendChild(canvas);
    }

    const image = document.createElement("img");
    image.id = imageId;
    Object.defineProperty(image, "complete", {
        value: complete,
        configurable: true,
    });
    Object.defineProperty(image, "naturalWidth", {
        value: naturalWidth,
        configurable: true,
    });
    Object.defineProperty(image, "naturalHeight", {
        value: naturalHeight,
        configurable: true,
    });
    document.body.appendChild(image);

    const ctx = createContext();
    canvas.getContext = jestGlobals.fn(() => ctx);
    canvas.getBoundingClientRect = jestGlobals.fn(() => ({
        left: 0,
        top: 0,
        width: 200,
        height: 200,
    }));

    const onCropChange = jestGlobals.fn();
    const selector = new CropSelector(canvasId, imageId, {
        ...options,
        onCropChange,
    });

    return { selector, canvas, image, ctx, onCropChange, container };
};

describe("crop_selector js/lib coverage", () => {
    beforeEach(async () => {
        jestGlobals.resetModules();
        document.body.innerHTML = "";
        try {
            delete window.devicePixelRatio;
        } catch {
            /* ignore */
        }

        const mod = await import("../lib/crop_selector.js");
        CropSelector = mod.default || mod;
    });

    test("constructor throws when required elements are missing", () => {
        expect(
            () => new CropSelector("missing-canvas", "missing-image"),
        ).toThrow("CropSelector: canvas or image element not found");
    });

    test("initializeCrop returns early when image dimensions are unavailable", () => {
        const { selector } = setupSelector({
            naturalWidth: 0,
            naturalHeight: 0,
        });
        const renderSpy = jestGlobals.spyOn(selector, "render");

        selector.initializeCrop();

        expect(renderSpy).not.toHaveBeenCalled();
        renderSpy.mockRestore();
    });

    test("initializeCrop emits and applies aspect ratio when configured", () => {
        const { selector, onCropChange } = setupSelector({
            options: { aspectRatio: 1 },
        });

        onCropChange.mockClear();
        selector.initializeCrop();

        expect(selector.cropBox.width).toBeGreaterThan(0);
        expect(selector.cropBox.height).toBeGreaterThan(0);
        expect(
            Math.abs(selector.cropBox.width - selector.cropBox.height),
        ).toBeLessThanOrEqual(1);
        expect(onCropChange).toHaveBeenCalled();
    });

    test("setAspectRatio handles null and numeric ratios", () => {
        const { selector } = setupSelector();
        const snapSpy = jestGlobals.spyOn(selector, "snapToAspectRatio");

        selector.setAspectRatio(null);
        expect(snapSpy).not.toHaveBeenCalled();

        selector.setAspectRatio(16 / 9);
        expect(snapSpy).toHaveBeenCalledWith(16 / 9);

        snapSpy.mockRestore();
    });

    test("snapToAspectRatio clamps when computed height exceeds canvas", () => {
        const { selector, canvas } = setupSelector();
        canvas.width = 200;
        canvas.height = 100;

        selector.snapToAspectRatio(0.1);

        expect(selector.cropBox.height).toBeLessThanOrEqual(100);
        expect(selector.cropBox.width).toBeLessThanOrEqual(200);
    });

    test("setCropBox and getCropBox use fallback scale when canvas scale is falsy", () => {
        const { selector } = setupSelector();

        selector.canvasToImageScale = 0;
        selector.setCropBox(12, 15, 40, 50);

        expect(selector.cropBox).toEqual({
            x: 12,
            y: 15,
            width: 40,
            height: 50,
        });
        expect(selector.getCropBox()).toEqual({
            x: 12,
            y: 15,
            width: 40,
            height: 50,
        });
    });

    test("setRotation returns early when degrees resolve to same angle", () => {
        const { selector } = setupSelector();
        const renderSpy = jestGlobals.spyOn(selector, "render");

        selector.setRotation(0);

        expect(renderSpy).not.toHaveBeenCalled();
        renderSpy.mockRestore();
    });

    test("setRotation reprojection branch works with aspect ratio and fallback inverse scale", () => {
        const { selector } = setupSelector();
        selector.aspectRatio = 1;
        selector.canvasToImageScale = 0;
        selector.cropBox = { x: 5, y: 5, width: 80, height: 40 };

        selector.setRotation(90);

        expect(selector.rotationDeg).toBe(90);
        expect(selector.cropBox.width).toBeGreaterThan(0);
        expect(selector.cropBox.height).toBeGreaterThan(0);
    });

    test("resize handle and inside checks cover hit and miss paths", () => {
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };

        expect(selector.getResizeHandle(10, 10)).toBe("tl");
        expect(selector.getResizeHandle(0, 0)).toBeNull();
        expect(selector.isInsideCropBox(20, 20)).toBe(true);
        expect(selector.isInsideCropBox(190, 190)).toBe(false);
    });

    test("onMouseDown returns early when image is not ready", () => {
        const { selector } = setupSelector({ complete: false });

        selector.onMouseDown({
            clientX: 20,
            clientY: 20,
            preventDefault: jestGlobals.fn(),
        });

        expect(selector.isResizing).toBe(false);
        expect(selector.isDragging).toBe(false);
    });

    test("onMouseDown starts resizing for handle and dragging inside crop box", () => {
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 100, height: 80 };

        const preventResize = jestGlobals.fn();
        selector.onMouseDown({
            clientX: 10,
            clientY: 10,
            preventDefault: preventResize,
        });
        expect(selector.isResizing).toBe(true);
        expect(selector.resizeHandle).toBe("tl");
        expect(preventResize).toHaveBeenCalled();

        selector.isResizing = false;
        selector.resizeHandle = null;

        const preventDrag = jestGlobals.fn();
        selector.onMouseDown({
            clientX: 50,
            clientY: 40,
            preventDefault: preventDrag,
        });
        expect(selector.isDragging).toBe(true);
        expect(preventDrag).toHaveBeenCalled();
    });

    test("onMouseDown outside crop box does not start interactions", () => {
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 80, height: 80 };

        selector.onMouseDown({
            clientX: 190,
            clientY: 190,
            preventDefault: jestGlobals.fn(),
        });

        expect(selector.isDragging).toBe(false);
        expect(selector.isResizing).toBe(false);
    });

    test("onMouseMove updates cursor for unknown handle, inside, and outside", () => {
        const { selector, canvas } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };

        selector.getResizeHandle = jestGlobals.fn(() => "weird");
        selector.onMouseMove({
            clientX: 20,
            clientY: 20,
            preventDefault: jestGlobals.fn(),
        });
        expect(canvas.style.cursor).toBe("move");

        selector.getResizeHandle = jestGlobals.fn(() => null);
        selector.onMouseMove({
            clientX: 40,
            clientY: 40,
            preventDefault: jestGlobals.fn(),
        });
        expect(canvas.style.cursor).toBe("move");

        selector.onMouseMove({
            clientX: 190,
            clientY: 190,
            preventDefault: jestGlobals.fn(),
        });
        expect(canvas.style.cursor).toBe("crosshair");
    });

    test("onMouseMove resizes all handles with and without aspect ratio", () => {
        const { selector } = setupSelector();
        const handles = ["tl", "tr", "bl", "br", "t", "b", "l", "r"];

        for (const handle of handles) {
            selector.cropBox = { x: 20, y: 20, width: 80, height: 60 };
            selector.isResizing = true;
            selector.resizeHandle = handle;
            selector.aspectRatio = 2;
            selector.onMouseMove({
                clientX: 140,
                clientY: 120,
                preventDefault: jestGlobals.fn(),
            });

            selector.cropBox = { x: 20, y: 20, width: 80, height: 60 };
            selector.isResizing = true;
            selector.resizeHandle = handle;
            selector.aspectRatio = null;
            selector.onMouseMove({
                clientX: 130,
                clientY: 110,
                preventDefault: jestGlobals.fn(),
            });
        }

        expect(selector.cropBox.width).toBeGreaterThan(0);
        expect(selector.cropBox.height).toBeGreaterThan(0);
    });

    test("onMouseMove drag path clamps within bounds for devicePixelRatio values", () => {
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 70, height: 60 };
        selector.isDragging = true;
        selector.dragStartX = 5;
        selector.dragStartY = 5;

        window.devicePixelRatio = 2;
        selector.onMouseMove({
            clientX: 120,
            clientY: 130,
            preventDefault: jestGlobals.fn(),
        });

        window.devicePixelRatio = 0;
        selector.onMouseMove({
            clientX: 150,
            clientY: 160,
            preventDefault: jestGlobals.fn(),
        });

        expect(selector.cropBox.x).toBeGreaterThanOrEqual(0);
        expect(selector.cropBox.y).toBeGreaterThanOrEqual(0);
    });

    test("onMouseMove returns early when image is not ready", () => {
        const { selector } = setupSelector({ complete: false });

        selector.onMouseMove({
            clientX: 20,
            clientY: 20,
            preventDefault: jestGlobals.fn(),
        });

        expect(selector.isDragging).toBe(false);
        expect(selector.isResizing).toBe(false);
    });

    test("onMouseUp clears drag and resize state", () => {
        const { selector } = setupSelector();
        selector.isDragging = true;
        selector.isResizing = true;
        selector.resizeHandle = "br";

        selector.onMouseUp();

        expect(selector.isDragging).toBe(false);
        expect(selector.isResizing).toBe(false);
        expect(selector.resizeHandle).toBeNull();
    });

    test("emitChange handles callback and no-callback branches", () => {
        const { selector, onCropChange } = setupSelector();
        selector.emitChange();
        expect(onCropChange).toHaveBeenCalled();

        const noCallbackSelector = setupSelector({ options: {} }).selector;
        noCallbackSelector.options = {};
        expect(() => noCallbackSelector.emitChange()).not.toThrow();
    });

    test("render returns early when image is incomplete", () => {
        const { selector, ctx, image } = setupSelector();
        ctx.drawImage.mockClear();
        Object.defineProperty(image, "complete", {
            value: false,
            configurable: true,
        });

        selector.render();

        expect(ctx.drawImage).not.toHaveBeenCalled();
    });

    test("render draws overlays, handles, and fallback width when no container", () => {
        const { selector, canvas, ctx } = setupSelector({
            withContainer: false,
        });
        selector.cropBox = { x: 10, y: 10, width: 80, height: 80 };

        // Force parentElement null to hit the maxW fallback path.
        canvas.remove();

        selector.render();

        expect(canvas.width).toBeGreaterThan(0);
        expect(canvas.height).toBeGreaterThan(0);
        expect(ctx.drawImage).toHaveBeenCalled();
        expect(ctx.fillRect).toHaveBeenCalled();
        expect(ctx.strokeRect).toHaveBeenCalled();
    });

    test("render rescales crop box when canvasToImageScale changes", () => {
        const { selector } = setupSelector();
        selector.canvasToImageScale = 2;
        selector.cropBox = { x: 12, y: 14, width: 50, height: 40 };

        selector.render();

        expect(selector.cropBox.width).toBeGreaterThan(0);
        expect(selector.cropBox.height).toBeGreaterThan(0);
    });

    test("geometry helpers cover clamp and ratio shrink branches", () => {
        const { selector } = setupSelector();

        const clamped = selector._clampRect(
            { x: -5, y: -5, width: 30, height: 30 },
            { x: 0, y: 0, width: 10, height: 12 },
        );
        expect(clamped.x).toBe(0);
        expect(clamped.y).toBe(0);

        const shrunkWide = selector._shrinkToRatio(
            { x: 90, y: 90, width: 40, height: 10 },
            1,
            100,
            100,
        );
        expect(shrunkWide.x + shrunkWide.width).toBeLessThanOrEqual(100);
        expect(shrunkWide.y + shrunkWide.height).toBeLessThanOrEqual(100);

        const shrunkTall = selector._shrinkToRatio(
            { x: -10, y: -10, width: 10, height: 40 },
            1,
            100,
            100,
        );
        expect(shrunkTall.x).toBeGreaterThanOrEqual(0);
        expect(shrunkTall.y).toBeGreaterThanOrEqual(0);

        const shrunkPositiveY = selector._shrinkToRatio(
            { x: 20, y: 20, width: 120, height: 120 },
            1,
            80,
            80,
        );
        expect(shrunkPositiveY.y + shrunkPositiveY.height).toBeLessThanOrEqual(
            80,
        );

        const shrunkNegativeY = selector._shrinkToRatio(
            { x: 10, y: -30, width: 60, height: 60 },
            1,
            80,
            80,
        );
        expect(shrunkNegativeY.y).toBeGreaterThanOrEqual(0);
    });

    test("rotation geometry helpers return bounded rectangles", () => {
        const { selector, image } = setupSelector({
            naturalWidth: 240,
            naturalHeight: 120,
        });

        const original = { x: 20, y: 10, width: 80, height: 40 };
        const rotated = selector._originalRectToRotatedBBox(
            original,
            Math.PI / 4,
            image.naturalWidth,
            image.naturalHeight,
        );
        const restored = selector._rotatedRectToOriginalBBox(
            rotated,
            Math.PI / 4,
            image.naturalWidth,
            image.naturalHeight,
        );

        expect(rotated.width).toBeGreaterThan(0);
        expect(rotated.height).toBeGreaterThan(0);
        expect(restored.x).toBeGreaterThanOrEqual(0);
        expect(restored.y).toBeGreaterThanOrEqual(0);
    });
});
