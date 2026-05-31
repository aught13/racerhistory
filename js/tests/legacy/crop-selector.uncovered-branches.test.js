import { jest } from "@jest/globals";

let CropSelector;
const createContext = () => ({
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
} = {}) => {
    document.body.innerHTML = "";
    const container = document.createElement("div");
    Object.defineProperty(container, "clientWidth", {
        value: clientWidth,
        configurable: true,
    });

    const canvas = document.createElement("canvas");
    canvas.id = "crop-canvas";
    canvas.width = 200;
    canvas.height = 200;
    container.appendChild(canvas);
    document.body.appendChild(container);

    const image = document.createElement("img");
    image.id = "crop-image";
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
    canvas.getContext = jest.fn(() => ctx);
    canvas.getBoundingClientRect = jest.fn(() => ({
        left: 0,
        top: 0,
        width: 200,
        height: 200,
    }));

    const selector = new CropSelector("crop-canvas", "crop-image", {});
    return { selector, canvas, image, ctx, container };
};

describe("crop-selector uncovered branches", () => {
    beforeEach(async () => {
        jest.resetModules();
        const mod = await import("../../legacy/crop-selector.js");
        CropSelector = mod.default || mod;

        jest.clearAllMocks();
        document.body.innerHTML = "";
        delete window.devicePixelRatio;
    });

    test("initializeCrop returns early when image has no size", () => {
        const { selector } = setupSelector({
            complete: true,
            naturalWidth: 0,
            naturalHeight: 0,
        });
        const renderSpy = jest.spyOn(selector, "render");

        selector.initializeCrop();

        expect(renderSpy).not.toHaveBeenCalled();
        renderSpy.mockRestore();
    });

    test("setAspectRatio null skips snapping", () => {
        const { selector } = setupSelector();
        const snapSpy = jest.spyOn(selector, "snapToAspectRatio");

        selector.setAspectRatio(null);

        expect(snapSpy).not.toHaveBeenCalled();
        snapSpy.mockRestore();
    });

    test("snapToAspectRatio clamps when height exceeds canvas", () => {
        const { selector, canvas } = setupSelector();
        canvas.width = 200;
        canvas.height = 100;

        selector.snapToAspectRatio(0.1);

        expect(selector.cropBox.height).toBeLessThanOrEqual(100);
        expect(selector.cropBox.width).toBeLessThanOrEqual(200);
    });

    test("setCropBox uses default scale when canvasToImageScale is falsy", () => {
        const { selector } = setupSelector();
        selector.canvasToImageScale = 0;

        selector.setCropBox(10, 20, 30, 40);

        expect(selector.cropBox.x).toBe(10);
        expect(selector.cropBox.y).toBe(20);
        expect(selector.cropBox.width).toBe(30);
        expect(selector.cropBox.height).toBe(40);
    });

    test("setRotation uses fallback scale when canvasToImageScale is falsy", () => {
        const { selector } = setupSelector();
        selector.canvasToImageScale = 0;
        selector.aspectRatio = null;
        selector.render = jest.fn();

        selector.setRotation(90);

        expect(selector.rotationDeg).toBe(90);
        expect(selector.cropBox.width).toBeGreaterThan(0);
    });

    test("onMouseDown and onMouseMove return early when image not ready", () => {
        const { selector } = setupSelector({ complete: false });
        selector.cropBox = { x: 10, y: 10, width: 50, height: 50 };

        selector.onMouseDown({
            clientX: 20,
            clientY: 20,
            preventDefault: jest.fn(),
        });
        selector.onMouseMove({
            clientX: 30,
            clientY: 30,
            preventDefault: jest.fn(),
        });

        expect(selector.isDragging).toBe(false);
        expect(selector.isResizing).toBe(false);
    });

    test("onMouseDown outside crop box does not start dragging", () => {
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 50, height: 50 };

        selector.onMouseDown({
            clientX: 180,
            clientY: 180,
            preventDefault: jest.fn(),
        });

        expect(selector.isDragging).toBe(false);
        expect(selector.isResizing).toBe(false);
    });

    test("cursor falls back to move when handle is unknown", () => {
        const { selector, canvas } = setupSelector();
        selector.getResizeHandle = jest.fn(() => "weird");

        selector.onMouseMove({
            clientX: 20,
            clientY: 20,
            preventDefault: jest.fn(),
        });

        expect(canvas.style.cursor).toBe("move");
    });

    test("onMouseMove resizes every handle with and without aspect ratio", () => {
        const { selector } = setupSelector();
        const handles = ["tl", "tr", "bl", "br", "t", "b", "l", "r"];
        const fireMove = () =>
            selector.onMouseMove({
                clientX: 140,
                clientY: 140,
                preventDefault: jest.fn(),
            });

        handles.forEach((handle) => {
            selector.cropBox = { x: 20, y: 20, width: 80, height: 80 };
            selector.aspectRatio = 2;
            selector.isResizing = true;
            selector.resizeHandle = handle;
            fireMove();

            selector.cropBox = { x: 20, y: 20, width: 80, height: 80 };
            selector.aspectRatio = null;
            selector.isResizing = true;
            selector.resizeHandle = handle;
            fireMove();
        });

        expect(selector.cropBox.width).toBeGreaterThan(0);
    });

    test("render fills all outside areas and uses devicePixelRatio", () => {
        window.devicePixelRatio = 2;
        const { selector, ctx } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 50, height: 50 };

        selector.render();

        expect(ctx.fillRect).toHaveBeenCalled();
        expect(ctx.drawImage).toHaveBeenCalled();
    });

    test("render uses fallback max width when canvas has no parent", () => {
        window.devicePixelRatio = 1;
        const { selector, canvas } = setupSelector();
        canvas.parentElement.removeChild(canvas);
        selector.cropBox = { x: 0, y: 0, width: 50, height: 50 };

        selector.render();

        expect(canvas.width).toBeGreaterThan(0);
    });

    test("dragging uses devicePixelRatio branch", () => {
        window.devicePixelRatio = 2;
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 60, height: 60 };
        selector.isDragging = true;
        selector.dragStartX = 5;
        selector.dragStartY = 5;

        selector.onMouseMove({
            clientX: 80,
            clientY: 90,
            preventDefault: jest.fn(),
        });

        expect(selector.cropBox.x).toBeGreaterThanOrEqual(0);
        expect(selector.cropBox.y).toBeGreaterThanOrEqual(0);
    });

    test("dragging uses fallback devicePixelRatio when zero", () => {
        window.devicePixelRatio = 0;
        const { selector } = setupSelector();
        selector.cropBox = { x: 10, y: 10, width: 60, height: 60 };
        selector.isDragging = true;
        selector.dragStartX = 5;
        selector.dragStartY = 5;

        selector.onMouseMove({
            clientX: 80,
            clientY: 90,
            preventDefault: jest.fn(),
        });

        expect(selector.cropBox.x).toBeGreaterThanOrEqual(0);
        expect(selector.cropBox.y).toBeGreaterThanOrEqual(0);
    });

    test("_shrinkToRatio clamps to bounds when outside", () => {
        const { selector } = setupSelector();

        const clampedNeg = selector._shrinkToRatio(
            { x: -10, y: -10, width: 5, height: 5 },
            1,
            100,
            100,
        );
        expect(clampedNeg.x).toBe(0);
        expect(clampedNeg.y).toBe(0);

        const clampedOver = selector._shrinkToRatio(
            { x: 90, y: 90, width: 30, height: 30 },
            1,
            100,
            100,
        );
        expect(clampedOver.x + clampedOver.width).toBeLessThanOrEqual(100);
        expect(clampedOver.y + clampedOver.height).toBeLessThanOrEqual(100);
    });

    test("render adjusts crop box when scale changes", () => {
        const { selector } = setupSelector();
        selector.canvasToImageScale = 2;
        selector.cropBox = { x: 10, y: 10, width: 60, height: 60 };

        selector.render();

        expect(selector.cropBox.width).toBeGreaterThan(0);
    });
});
