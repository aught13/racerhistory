import { jest } from "@jest/globals";

/* crop-selector.branches.test.js
 * Focused tests for webroot/js/crop-selector.js
 */

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    try {
        delete window.devicePixelRatio;
    } catch {
        /* ignore */
    }
});

test("constructor throws when elements missing", async () => {
    const _csmod = await import("../crop-selector.js");
    const CropSelector = _csmod.default || _csmod;
    expect(() => new CropSelector("nope", "nope2")).toThrow();
});

test("initializeCrop sets full crop and emits change", async () => {
    const container = document.createElement("div");
    Object.defineProperty(container, "clientWidth", {
        value: 800,
        configurable: true,
    });
    const canvas = document.createElement("canvas");
    canvas.id = "cv";
    container.appendChild(canvas);
    document.body.appendChild(container);
    const img = document.createElement("img");
    img.id = "img";
    Object.defineProperty(img, "naturalWidth", {
        value: 400,
        configurable: true,
    });
    Object.defineProperty(img, "naturalHeight", {
        value: 200,
        configurable: true,
    });
    Object.defineProperty(img, "complete", { value: true, configurable: true });
    document.body.appendChild(img);

    // stub 2D context used by render
    const ctx = {
        setTransform: jest.fn(),
        imageSmoothingEnabled: true,
        imageSmoothingQuality: "high",
        save: jest.fn(),
        restore: jest.fn(),
        translate: jest.fn(),
        rotate: jest.fn(),
        scale: jest.fn(),
        drawImage: jest.fn(),
        fillRect: jest.fn(),
        strokeRect: jest.fn(),
        beginPath: jest.fn(),
        moveTo: jest.fn(),
        lineTo: jest.fn(),
        stroke: jest.fn(),
    };
    canvas.getContext = () => ctx;

    const onChange = jest.fn();
    const _csmod = await import("../crop-selector.js");
    const CropSelector = _csmod.default || _csmod;
    const sel = new CropSelector(canvas.id, img.id, { onCropChange: onChange });

    // ensure initializeCrop ran (call explicitly to be deterministic)
    sel.initializeCrop();
    await Promise.resolve();
    const cb = sel.getCropBox();
    expect(cb.width).toBeGreaterThan(0);
    expect(cb.height).toBeGreaterThan(0);
    // verify option wired and emitChange triggers callback
    expect(typeof sel.options.onCropChange).toBe("function");
    sel.emitChange();
    expect(onChange).toHaveBeenCalled();
});

test("setAspectRatio snaps crop to ratio", async () => {
    const container = document.createElement("div");
    Object.defineProperty(container, "clientWidth", {
        value: 800,
        configurable: true,
    });
    const canvas = document.createElement("canvas");
    canvas.id = "cv2";
    container.appendChild(canvas);
    document.body.appendChild(container);
    const img = document.createElement("img");
    img.id = "img2";
    Object.defineProperty(img, "naturalWidth", {
        value: 400,
        configurable: true,
    });
    Object.defineProperty(img, "naturalHeight", {
        value: 200,
        configurable: true,
    });
    Object.defineProperty(img, "complete", { value: true, configurable: true });
    document.body.appendChild(img);

    const ctx = {
        setTransform: () => {},
        save: () => {},
        restore: () => {},
        translate: () => {},
        rotate: () => {},
        scale: () => {},
        drawImage: () => {},
        fillRect: () => {},
        strokeRect: () => {},
        beginPath: () => {},
        moveTo: () => {},
        lineTo: () => {},
        stroke: () => {},
    };
    canvas.getContext = () => ctx;

    const _csmod = await import("../crop-selector.js");
    const CropSelector = _csmod.default || _csmod;
    const sel = new CropSelector(canvas.id, img.id, {});
    await Promise.resolve();
    sel.setAspectRatio(1);
    await Promise.resolve();
    const { width, height } = sel.cropBox;
    // ratio should be close to 1
    expect(Math.abs(width / height - 1)).toBeLessThan(0.01);
});

test("setCropBox and getCropBox round-trip with canvasToImageScale", async () => {
    const container = document.createElement("div");
    Object.defineProperty(container, "clientWidth", {
        value: 800,
        configurable: true,
    });
    const canvas = document.createElement("canvas");
    canvas.id = "cv3";
    container.appendChild(canvas);
    document.body.appendChild(container);
    const img = document.createElement("img");
    img.id = "img3";
    Object.defineProperty(img, "naturalWidth", {
        value: 400,
        configurable: true,
    });
    Object.defineProperty(img, "naturalHeight", {
        value: 200,
        configurable: true,
    });
    Object.defineProperty(img, "complete", { value: true, configurable: true });
    document.body.appendChild(img);

    const ctx = {
        setTransform: () => {},
        save: () => {},
        restore: () => {},
        translate: () => {},
        rotate: () => {},
        scale: () => {},
        drawImage: () => {},
        fillRect: () => {},
        strokeRect: () => {},
        beginPath: () => {},
        moveTo: () => {},
        lineTo: () => {},
        stroke: () => {},
    };
    canvas.getContext = () => ctx;

    const _csmod = await import("../crop-selector.js");
    const CropSelector = _csmod.default || _csmod;
    const sel = new CropSelector(canvas.id, img.id, {});
    // set a known canvasToImageScale
    sel.canvasToImageScale = 2;
    sel.setCropBox(20, 30, 40, 50);
    const cb = sel.getCropBox();
    expect(cb.x).toBe(20);
    expect(cb.y).toBe(30);
    expect(cb.width).toBe(40);
    expect(cb.height).toBe(50);
});

test("getResizeHandle and isInsideCropBox detect positions", async () => {
    const canvas = document.createElement("canvas");
    canvas.id = "cv4";
    document.body.appendChild(canvas);
    const img = document.createElement("img");
    img.id = "img4";
    Object.defineProperty(img, "naturalWidth", {
        value: 200,
        configurable: true,
    });
    Object.defineProperty(img, "naturalHeight", {
        value: 200,
        configurable: true,
    });
    Object.defineProperty(img, "complete", { value: true, configurable: true });
    document.body.appendChild(img);
    const ctx = {
        setTransform: () => {},
        save: () => {},
        restore: () => {},
        translate: () => {},
        rotate: () => {},
        scale: () => {},
        drawImage: () => {},
        fillRect: () => {},
        strokeRect: () => {},
        beginPath: () => {},
        moveTo: () => {},
        lineTo: () => {},
        stroke: () => {},
    };
    canvas.getContext = () => ctx;

    const _csmod = await import("../crop-selector.js");
    const CropSelector = _csmod.default || _csmod;
    const sel = new CropSelector(canvas.id, img.id, {});
    // set cropBox to known values
    sel.cropBox = { x: 10, y: 10, width: 100, height: 80 };
    // handle near top-left
    const h = sel.getResizeHandle(10, 10);
    expect(h).toBeTruthy();
    // inside
    expect(sel.isInsideCropBox(20, 20)).toBe(true);
    expect(sel.isInsideCropBox(0, 0)).toBe(false);
});
