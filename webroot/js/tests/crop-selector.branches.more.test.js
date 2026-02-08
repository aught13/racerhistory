const CropSelector = require("../crop-selector.js");

function mockCanvasContext() {
    return {
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
        lineWidth: 0,
        imageSmoothingEnabled: false,
        imageSmoothingQuality: "low",
    };
}

function setupCanvasAndImage() {
    document.body.innerHTML = `
    <div id="wrap">
      <canvas id="canvas"></canvas>
      <img id="image" />
    </div>
  `;

    const canvas = document.getElementById("canvas");
    const image = document.getElementById("image");

    const ctx = mockCanvasContext();
    canvas.getContext = jest.fn(() => ctx);
    canvas.getBoundingClientRect = () => ({
        left: 0,
        top: 0,
        width: 200,
        height: 200,
    });
    canvas.width = 200;
    canvas.height = 200;
    Object.defineProperty(canvas.parentElement, "clientWidth", { value: 300 });

    Object.defineProperty(image, "complete", {
        value: true,
        configurable: true,
    });
    Object.defineProperty(image, "naturalWidth", {
        value: 200,
        configurable: true,
    });
    Object.defineProperty(image, "naturalHeight", {
        value: 200,
        configurable: true,
    });

    return { canvas, image, ctx };
}

describe("crop-selector branch coverage", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
    });

    test("setAspectRatio snaps crop to ratio", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.setAspectRatio(1);

        expect(
            Math.abs(selector.cropBox.width - selector.cropBox.height),
        ).toBeLessThanOrEqual(1);
    });

    test("getResizeHandle identifies top-left handle and null outside", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };

        expect(selector.getResizeHandle(10, 10)).toBe("tl");
        expect(selector.getResizeHandle(0, 0)).toBeNull();
    });

    test("onMouseDown starts resizing when handle hit", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };

        selector.onMouseDown({
            clientX: 10,
            clientY: 10,
            preventDefault: jest.fn(),
        });

        expect(selector.isResizing).toBe(true);
        expect(selector.resizeHandle).toBe("tl");
    });

    test("onMouseMove resizes with aspect ratio", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.aspectRatio = 1;
        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };
        selector.isResizing = true;
        selector.resizeHandle = "br";

        selector.onMouseMove({
            clientX: 150,
            clientY: 150,
            preventDefault: jest.fn(),
        });

        expect(
            Math.abs(selector.cropBox.width - selector.cropBox.height),
        ).toBeLessThanOrEqual(1);
    });

    test("onMouseMove resizes without aspect ratio for top handle", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.aspectRatio = null;
        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };
        selector.isResizing = true;
        selector.resizeHandle = "t";

        selector.onMouseMove({
            clientX: 60,
            clientY: 5,
            preventDefault: jest.fn(),
        });

        expect(selector.cropBox.height).toBeGreaterThanOrEqual(20);
    });

    test("onMouseMove resizes without aspect ratio for right handle", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.aspectRatio = null;
        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };
        selector.isResizing = true;
        selector.resizeHandle = "r";

        selector.onMouseMove({
            clientX: 140,
            clientY: 60,
            preventDefault: jest.fn(),
        });

        expect(selector.cropBox.width).toBeGreaterThanOrEqual(20);
    });

    test("onMouseMove resizes with aspect ratio for left and bottom handles", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.aspectRatio = 2;
        selector.cropBox = { x: 30, y: 30, width: 100, height: 50 };
        selector.isResizing = true;
        selector.resizeHandle = "l";

        selector.onMouseMove({
            clientX: 10,
            clientY: 50,
            preventDefault: jest.fn(),
        });
        expect(
            Math.abs(selector.cropBox.width - selector.cropBox.height * 2),
        ).toBeLessThanOrEqual(1);

        selector.resizeHandle = "b";
        selector.onMouseMove({
            clientX: 60,
            clientY: 120,
            preventDefault: jest.fn(),
        });
        expect(
            Math.abs(selector.cropBox.width - selector.cropBox.height * 2),
        ).toBeLessThanOrEqual(1);
    });

    test("onMouseDown inside crop box starts dragging", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.cropBox = { x: 20, y: 20, width: 80, height: 80 };

        selector.onMouseDown({
            clientX: 40,
            clientY: 40,
            preventDefault: jest.fn(),
        });

        expect(selector.isDragging).toBe(true);
        expect(selector.dragStartX).toBeGreaterThan(0);
        expect(selector.dragStartY).toBeGreaterThan(0);
    });

    test("onMouseMove updates cursor based on handle and position", () => {
        const { canvas } = setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.cropBox = { x: 10, y: 10, width: 100, height: 100 };

        selector.onMouseMove({
            clientX: 10,
            clientY: 10,
            preventDefault: jest.fn(),
        });
        expect(canvas.style.cursor).toBe("nwse-resize");

        selector.onMouseMove({
            clientX: 50,
            clientY: 50,
            preventDefault: jest.fn(),
        });
        expect(canvas.style.cursor).toBe("move");

        selector.onMouseMove({
            clientX: 180,
            clientY: 180,
            preventDefault: jest.fn(),
        });
        expect(canvas.style.cursor).toBe("crosshair");
    });

    test("onMouseMove drags crop box within bounds", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.cropBox = { x: 20, y: 20, width: 80, height: 80 };
        selector.isDragging = true;
        selector.dragStartX = 10;
        selector.dragStartY = 10;

        selector.onMouseMove({
            clientX: 60,
            clientY: 70,
            preventDefault: jest.fn(),
        });

        expect(selector.cropBox.x).toBeGreaterThanOrEqual(0);
        expect(selector.cropBox.y).toBeGreaterThanOrEqual(0);
    });

    test("setRotation updates crop box for rotated image", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        selector.cropBox = { x: 10, y: 10, width: 80, height: 60 };
        selector.setRotation(90);

        expect(selector.rotationDeg).toBe(90);
        expect(selector.cropBox.width).toBeGreaterThan(0);
        expect(selector.cropBox.height).toBeGreaterThan(0);
    });

    test("_clampRect clamps to bounds", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        const rect = selector._clampRect(
            { x: -5, y: -5, width: 20, height: 20 },
            { x: 0, y: 0, width: 10, height: 10 },
        );

        expect(rect.x).toBe(0);
        expect(rect.y).toBe(0);
        expect(rect.width).toBeGreaterThan(0);
        expect(rect.height).toBeGreaterThan(0);
    });

    test("_shrinkToRatio fits inside bounds", () => {
        setupCanvasAndImage();
        const selector = new CropSelector("canvas", "image");

        const rect = selector._shrinkToRatio(
            { x: 0, y: 0, width: 120, height: 60 },
            1,
            100,
            100,
        );

        expect(rect.width).toBeLessThanOrEqual(100);
        expect(rect.height).toBeLessThanOrEqual(100);
        expect(Math.abs(rect.width - rect.height)).toBeLessThanOrEqual(1);
    });
});
