/** @jest-environment jsdom */

import { jest } from "@jest/globals";
let CropSelector;

const createFakeContext = () => {
    const ctx = {
        _fillStyle: "#000",
        _strokeStyle: "#000",
        setTransform: jest.fn(),
        save: jest.fn(),
        translate: jest.fn(),
        rotate: jest.fn(),
        scale: jest.fn(),
        drawImage: jest.fn(),
        restore: jest.fn(),
        fillRect: jest.fn(),
        strokeRect: jest.fn(),
        beginPath: jest.fn(),
        moveTo: jest.fn(),
        lineTo: jest.fn(),
        stroke: jest.fn(),
        set fillStyle(value) {
            this._fillStyle = value;
        },
        get fillStyle() {
            return this._fillStyle;
        },
        set strokeStyle(value) {
            this._strokeStyle = value;
        },
        get strokeStyle() {
            return this._strokeStyle;
        },
        lineWidth: 1,
        imageSmoothingEnabled: false,
        imageSmoothingQuality: "high",
    };
    return ctx;
};

const setupCropSelector = () => {
    document.body.innerHTML = "";
    const container = document.createElement("div");
    container.id = "crop-container";
    Object.defineProperty(container, "clientWidth", {
        value: 600,
        configurable: true,
    });

    const canvas = document.createElement("canvas");
    canvas.id = "crop-canvas";
    canvas.width = 400;
    canvas.height = 300;
    container.appendChild(canvas);
    document.body.appendChild(container);

    const image = document.createElement("img");
    image.id = "crop-image";
    Object.defineProperty(image, "complete", {
        value: true,
        configurable: true,
    });
    Object.defineProperty(image, "naturalWidth", {
        value: 400,
        configurable: true,
    });
    Object.defineProperty(image, "naturalHeight", {
        value: 300,
        configurable: true,
    });
    document.body.appendChild(image);

    const ctx = createFakeContext();
    canvas.getContext = jest.fn(() => ctx);
    canvas.getBoundingClientRect = jest.fn(() => ({
        left: 0,
        top: 0,
        width: 400,
        height: 300,
    }));

    const onCropChange = jest.fn();
    const selector = new CropSelector("crop-canvas", "crop-image", {
        onCropChange,
    });
    return { selector, onCropChange, canvas, image, ctx };
};

describe("CropSelector interactions", () => {
    beforeEach(async () => {
        jest.clearAllMocks();
        jest.resetModules();
        const mod = await import("../../legacy/crop-selector.js");
        CropSelector = mod.default || mod;
    });

    test("constructor throws when DOM nodes are missing", () => {
        document.body.innerHTML = "";
        expect(() => new CropSelector("missing", "also-missing")).toThrow(
            "CropSelector: canvas or image element not found",
        );
    });

    test("setCropBox clamps values and emits change", () => {
        const { selector, onCropChange } = setupCropSelector();
        onCropChange.mockClear();

        selector.setCropBox(-50, -50, 1000, 1000);

        expect(selector.cropBox.x).toBeGreaterThanOrEqual(0);
        expect(selector.cropBox.y).toBeGreaterThanOrEqual(0);
        expect(selector.cropBox.width).toBeGreaterThanOrEqual(20);
        expect(selector.cropBox.height).toBeGreaterThanOrEqual(20);
        expect(onCropChange).toHaveBeenCalled();
        expect(selector.getCropBox().width).toBeGreaterThan(0);
    });

    test("setAspectRatio applies ratio and notifies listener", () => {
        const { selector, onCropChange } = setupCropSelector();
        onCropChange.mockClear();

        selector.setAspectRatio(16 / 9);

        const ratio = selector.cropBox.width / selector.cropBox.height;
        expect(ratio).toBeCloseTo(16 / 9, 1);
        expect(onCropChange).toHaveBeenCalled();
    });

    test("setRotation returns early when angle unchanged", () => {
        const { selector } = setupCropSelector();
        const renderSpy = jest.spyOn(selector, "render");
        renderSpy.mockClear();

        selector.setRotation(0);

        expect(renderSpy).not.toHaveBeenCalled();
        renderSpy.mockRestore();
    });

    test("setRotation reprojects crop box with locked aspect ratio", () => {
        const { selector, onCropChange } = setupCropSelector();
        selector.setAspectRatio(1);
        onCropChange.mockClear();

        selector.setRotation(90);

        expect(selector.rotationDeg).toBe(90);
        expect(onCropChange).toHaveBeenCalled();
    });

    test("helper utilities detect handles and inside points", () => {
        const { selector } = setupCropSelector();
        selector.cropBox = { x: 10, y: 10, width: 100, height: 80 };

        expect(selector.getResizeHandle(12, 12)).toBe("tl");
        expect(selector.isInsideCropBox(50, 45)).toBe(true);
        expect(selector.isInsideCropBox(0, 0)).toBe(false);
    });

    test("render exits early when image is not ready", () => {
        const { selector, ctx, image } = setupCropSelector();
        ctx.drawImage.mockClear();
        Object.defineProperty(image, "complete", {
            value: false,
            configurable: true,
        });

        selector.render();

        expect(ctx.drawImage).not.toHaveBeenCalled();
        Object.defineProperty(image, "complete", {
            value: true,
            configurable: true,
        });
    });

    test("geometry helpers clamp and shrink to ratio", () => {
        const { selector } = setupCropSelector();
        const clamped = selector._clampRect(
            { x: -5, y: -5, width: 500, height: 400 },
            { x: 0, y: 0, width: 100, height: 100 },
        );
        expect(clamped.x).toBe(0);
        expect(clamped.width).toBeLessThanOrEqual(100);

        const shrunk = selector._shrinkToRatio(
            { x: 0, y: 0, width: 80, height: 80 },
            16 / 9,
            80,
            80,
        );
        expect(shrunk.width / shrunk.height).toBeCloseTo(16 / 9, 2);
    });

    test("getCropBox accounts for canvasToImageScale", () => {
        const { selector } = setupCropSelector();
        selector.canvasToImageScale = 2;
        selector.cropBox = { x: 5, y: 10, width: 20, height: 30 };

        expect(selector.getCropBox()).toEqual({
            x: 10,
            y: 20,
            width: 40,
            height: 60,
        });
    });
});
