/**
 * CropSelector: Interactive crop box selection for images with aspect ratio locking
 *
 * Usage:
 *   const selector = new CropSelector('canvasId', 'imageId', {
 *       onCropChange: (crop) => console.log(crop)
 *   });
 *   selector.setAspectRatio(16/9); // or null for free-form
 */
// eslint-disable-next-line no-unused-vars
class CropSelector {
    constructor(canvasId, imageId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        this.image = document.getElementById(imageId);
        this.options = options;

        if (!this.canvas || !this.image) {
            throw new Error('CropSelector: canvas or image element not found');
        }

        this.ctx = this.canvas.getContext('2d');
        this.isDragging = false;
        this.isResizing = false;
        this.resizeHandle = null;
        this.aspectRatio = null; // null means free-form, otherwise width/height ratio
        this.rotationDeg = 0; // rotation applied to preview (clockwise degrees)

        // Crop box: canvas display coordinates
        this.cropBox = { x: 0, y: 0, width: 100, height: 100 };

        // Canvas-to-rotated-image scale (canvas px -> rotated image px)
        this.canvasToImageScale = 1;

        // Handle sizes
        this.handleSize = 10;

        // Register event listeners
        this.registerEvents();

        // Initialize when image loads
        this.image.addEventListener('load', () => this.initializeCrop());
        if (this.image.complete && this.image.naturalWidth) {
            this.initializeCrop();
        }
    }

    /**
     * Initialize crop box to full image size
     */
    initializeCrop() {
        if (!this.image.naturalWidth || !this.image.naturalHeight) return;
        // Render once to set canvas size and scale
        this.render();
        // Set crop to full displayed area
        const displayW = this.canvas.width / (window.devicePixelRatio || 1);
        const displayH = this.canvas.height / (window.devicePixelRatio || 1);
        this.cropBox = { x: 0, y: 0, width: displayW, height: displayH };
        // If aspect ratio locked, snap
        if (this.aspectRatio) {
            this.snapToAspectRatio(this.aspectRatio);
        }
        this.render();
        this.emitChange();
    }

    registerEvents() {
        this.canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
        this.canvas.addEventListener('mouseup', () => this.onMouseUp());
        this.canvas.addEventListener('mouseleave', () => this.onMouseUp());

        // Touch support
        this.canvas.addEventListener('touchstart', (e) => this.onMouseDown(e.touches[0]));
        this.canvas.addEventListener('touchmove', (e) => this.onMouseMove(e.touches[0]));
        this.canvas.addEventListener('touchend', () => this.onMouseUp());
    }

    /**
     * Get crop box in original image coordinates
     */
    getCropBox() {
        return {
            x: Math.round(this.cropBox.x * this.canvasToImageScale),
            y: Math.round(this.cropBox.y * this.canvasToImageScale),
            width: Math.round(this.cropBox.width * this.canvasToImageScale),
            height: Math.round(this.cropBox.height * this.canvasToImageScale),
        };
    }

    /**
     * Set crop box from original image coordinates
     */
    setCropBox(x, y, width, height) {
        const inv = 1 / (this.canvasToImageScale || 1);
        this.cropBox = {
            x: Math.round(x * inv),
            y: Math.round(y * inv),
            width: Math.round(width * inv),
            height: Math.round(height * inv),
        };
        this.clampCropBox();
        this.render();
        this.emitChange();
    }

    /**
     * Set aspect ratio lock (e.g., 16/9, 1, null for free-form)
     */
    setAspectRatio(ratio) {
        this.aspectRatio = ratio;
        if (ratio !== null) {
            this.snapToAspectRatio(ratio);
        }
        this.render();
        this.emitChange();
    }

    /**
     * Snap crop to the largest centered rectangle of the given aspect ratio.
     */
    snapToAspectRatio(ratio) {
        const canvasW = this.canvas.width / (window.devicePixelRatio || 1);
        const canvasH = this.canvas.height / (window.devicePixelRatio || 1);
        // Fit box of ratio within canvas
        let w = canvasW;
        let h = w / ratio;
        if (h > canvasH) {
            h = canvasH;
            w = h * ratio;
        }
        const x = (canvasW - w) / 2;
        const y = (canvasH - h) / 2;
        this.cropBox = { x: Math.round(x), y: Math.round(y), width: Math.round(w), height: Math.round(h) };
        this.clampCropBox();
    }

    /**
     * Set rotation in degrees (clockwise), re-render and reset crop to fit.
     */
    setRotation(degrees) {
        const parsed = Number(degrees) || 0;
        if (parsed === this.rotationDeg) return;

        const oldAngle = (this.rotationDeg % 360) * Math.PI / 180;
        const newAngle = (parsed % 360) * Math.PI / 180;

        // Current rotated-image rect from canvas cropBox
        const oldRotRect = {
            x: this.cropBox.x * this.canvasToImageScale,
            y: this.cropBox.y * this.canvasToImageScale,
            width: this.cropBox.width * this.canvasToImageScale,
            height: this.cropBox.height * this.canvasToImageScale,
        };

        // Convert old rotated rect -> original image bbox
        const srcW = this.image.naturalWidth;
        const srcH = this.image.naturalHeight;
        const originalRect = this._rotatedRectToOriginalBBox(oldRotRect, oldAngle, srcW, srcH);

        // Project original bbox into new rotated-image bbox
        const newRotRectRaw = this._originalRectToRotatedBBox(originalRect, newAngle, srcW, srcH);

        // Update angle and re-render to compute new canvas scale
        this.rotationDeg = parsed;
        this.render();

        // Clamp to rotated image bounds and enforce aspect ratio (shrink only), keep center
        const rotDims = this._rotatedDims(newAngle, srcW, srcH);
        let newRotRect = this._clampRect(newRotRectRaw, { x: 0, y: 0, width: rotDims.w, height: rotDims.h });
        if (this.aspectRatio) {
            newRotRect = this._shrinkToRatio(newRotRect, this.aspectRatio, rotDims.w, rotDims.h);
        }

        // Convert rotated rect to canvas cropBox
        const invScale = 1 / (this.canvasToImageScale || 1);
        this.cropBox = {
            x: Math.round(newRotRect.x * invScale),
            y: Math.round(newRotRect.y * invScale),
            width: Math.round(newRotRect.width * invScale),
            height: Math.round(newRotRect.height * invScale),
        };
        this.clampCropBox();
        this.render();
        this.emitChange();
    }

    /**
     * Clamp crop box to canvas bounds
     */
    clampCropBox() {
        const canvasW = this.canvas.width / (window.devicePixelRatio || 1);
        const canvasH = this.canvas.height / (window.devicePixelRatio || 1);

        this.cropBox.x = Math.max(0, Math.min(this.cropBox.x, canvasW - 1));
        this.cropBox.y = Math.max(0, Math.min(this.cropBox.y, canvasH - 1));
        this.cropBox.width = Math.max(20, Math.min(this.cropBox.width, canvasW - this.cropBox.x));
        this.cropBox.height = Math.max(20, Math.min(this.cropBox.height, canvasH - this.cropBox.y));
    }

    /**
     * Check if point is within crop box handles
     */
    getResizeHandle(px, py) {
        const { x, y, width, height } = this.cropBox;
        const h = this.handleSize;

        const handles = [
            { name: 'tl', x: x - h / 2, y: y - h / 2, w: h, h: h },
            { name: 'tr', x: x + width - h / 2, y: y - h / 2, w: h, h: h },
            { name: 'bl', x: x - h / 2, y: y + height - h / 2, w: h, h: h },
            { name: 'br', x: x + width - h / 2, y: y + height - h / 2, w: h, h: h },
            { name: 't', x: x + width / 2 - h / 2, y: y - h / 2, w: h, h: h },
            { name: 'b', x: x + width / 2 - h / 2, y: y + height - h / 2, w: h, h: h },
            { name: 'l', x: x - h / 2, y: y + height / 2 - h / 2, w: h, h: h },
            { name: 'r', x: x + width - h / 2, y: y + height / 2 - h / 2, w: h, h: h },
        ];

        for (const handle of handles) {
            if (px >= handle.x && px < handle.x + handle.w && py >= handle.y && py < handle.y + handle.h) {
                return handle.name;
            }
        }
        return null;
    }

    /**
     * Check if point is within crop box
     */
    isInsideCropBox(px, py) {
        const { x, y, width, height } = this.cropBox;
        return px >= x && px < x + width && py >= y && py < y + height;
    }

    onMouseDown(e) {
        if (!this.image.complete || !this.image.naturalWidth) return;

        const rect = this.canvas.getBoundingClientRect();
        const px = (e.clientX - rect.left) * (this.canvas.width / (window.devicePixelRatio || 1) / rect.width);
        const py = (e.clientY - rect.top) * (this.canvas.height / (window.devicePixelRatio || 1) / rect.height);

        const handle = this.getResizeHandle(px, py);
        if (handle) {
            this.isResizing = true;
            this.resizeHandle = handle;
            e.preventDefault();
            return;
        }

        if (this.isInsideCropBox(px, py)) {
            this.isDragging = true;
            this.dragStartX = px - this.cropBox.x;
            this.dragStartY = py - this.cropBox.y;
            e.preventDefault();
        }
    }

    onMouseMove(e) {
        if (!this.image.complete || !this.image.naturalWidth) return;

        const rect = this.canvas.getBoundingClientRect();
        const px = (e.clientX - rect.left) * (this.canvas.width / (window.devicePixelRatio || 1) / rect.width);
        const py = (e.clientY - rect.top) * (this.canvas.height / (window.devicePixelRatio || 1) / rect.height);

        // Update cursor
        if (!this.isDragging && !this.isResizing) {
            const handle = this.getResizeHandle(px, py);
            if (handle) {
                const cursors = {
                    tl: 'nwse-resize', tr: 'nesw-resize',
                    bl: 'nesw-resize', br: 'nwse-resize',
                    t: 'ns-resize', b: 'ns-resize',
                    l: 'ew-resize', r: 'ew-resize',
                };
                this.canvas.style.cursor = cursors[handle] || 'move';
            } else if (this.isInsideCropBox(px, py)) {
                this.canvas.style.cursor = 'move';
            } else {
                this.canvas.style.cursor = 'crosshair';
            }
        }

        // Resize with aspect ratio support
        if (this.isResizing && this.resizeHandle) {
            e.preventDefault();
            const minSize = 20;
            const { x, y, width, height } = this.cropBox;
            const ar = this.aspectRatio;

            switch (this.resizeHandle) {
                case 'tl':
                    this.cropBox.width = Math.max(minSize, x + width - px);
                    this.cropBox.height = ar ? this.cropBox.width / ar : Math.max(minSize, y + height - py);
                    this.cropBox.x = x + width - this.cropBox.width;
                    this.cropBox.y = y + height - this.cropBox.height;
                    break;
                case 'tr':
                    this.cropBox.width = Math.max(minSize, px - x);
                    this.cropBox.height = ar ? this.cropBox.width / ar : Math.max(minSize, y + height - py);
                    this.cropBox.y = y + height - this.cropBox.height;
                    break;
                case 'bl':
                    this.cropBox.width = Math.max(minSize, x + width - px);
                    this.cropBox.height = ar ? this.cropBox.width / ar : Math.max(minSize, py - y);
                    this.cropBox.x = x + width - this.cropBox.width;
                    break;
                case 'br':
                    this.cropBox.width = Math.max(minSize, px - x);
                    this.cropBox.height = ar ? this.cropBox.width / ar : Math.max(minSize, py - y);
                    break;
                case 't':
                    if (ar) {
                        this.cropBox.height = Math.max(minSize, y + height - py);
                        this.cropBox.width = this.cropBox.height * ar;
                        this.cropBox.x = x + (width - this.cropBox.width) / 2;
                    } else {
                        this.cropBox.height = Math.max(minSize, y + height - py);
                    }
                    this.cropBox.y = y + height - this.cropBox.height;
                    break;
                case 'b':
                    if (ar) {
                        this.cropBox.height = Math.max(minSize, py - y);
                        this.cropBox.width = this.cropBox.height * ar;
                        this.cropBox.x = x + (width - this.cropBox.width) / 2;
                    } else {
                        this.cropBox.height = Math.max(minSize, py - y);
                    }
                    break;
                case 'l':
                    if (ar) {
                        this.cropBox.width = Math.max(minSize, x + width - px);
                        this.cropBox.height = this.cropBox.width / ar;
                        this.cropBox.y = y + (height - this.cropBox.height) / 2;
                    } else {
                        this.cropBox.width = Math.max(minSize, x + width - px);
                    }
                    this.cropBox.x = x + width - this.cropBox.width;
                    break;
                case 'r':
                    if (ar) {
                        this.cropBox.width = Math.max(minSize, px - x);
                        this.cropBox.height = this.cropBox.width / ar;
                        this.cropBox.y = y + (height - this.cropBox.height) / 2;
                    } else {
                        this.cropBox.width = Math.max(minSize, px - x);
                    }
                    break;
            }

            this.clampCropBox();
            this.render();
            this.emitChange();
        }

        // Drag
        if (this.isDragging) {
            e.preventDefault();
            const newX = px - this.dragStartX;
            const newY = py - this.dragStartY;

            this.cropBox.x = Math.max(0, Math.min(newX, (this.canvas.width / (window.devicePixelRatio || 1)) - this.cropBox.width));
            this.cropBox.y = Math.max(0, Math.min(newY, (this.canvas.height / (window.devicePixelRatio || 1)) - this.cropBox.height));

            this.render();
            this.emitChange();
        }
    }

    onMouseUp() {
        this.isDragging = false;
        this.isResizing = false;
        this.resizeHandle = null;
    }

    emitChange() {
        if (this.options.onCropChange) {
            this.options.onCropChange(this.getCropBox());
        }
    }

    /**
     * Render the image and crop box on canvas
     */
    render() {
        if (!this.image.complete || !this.image.naturalWidth || !this.image.naturalHeight) {
            return;
        }

        const srcW = this.image.naturalWidth;
        const srcH = this.image.naturalHeight;
        const container = this.canvas.parentElement;
        const maxW = container ? container.clientWidth - 40 : 600;
        const maxH = 500;

        // Compute rotated bounding box
        const rad = (this.rotationDeg % 360) * Math.PI / 180;
        const absCos = Math.abs(Math.cos(rad));
        const absSin = Math.abs(Math.sin(rad));
        const rotW = srcW * absCos + srcH * absSin;
        const rotH = srcW * absSin + srcH * absCos;

        // Fit rotated image to container
        const fitScale = Math.min(maxW / rotW, maxH / rotH, 1);
        const displayW = Math.max(1, Math.round(rotW * fitScale));
        const displayH = Math.max(1, Math.round(rotH * fitScale));

        // Update canvas->image scale
        const oldScale = this.canvasToImageScale;
        this.canvasToImageScale = 1 / fitScale; // canvas px -> rotated image px

        // If scale changed significantly, adjust crop box to keep relative size
        if (oldScale > 0 && Math.abs(oldScale - this.canvasToImageScale) > 0.001) {
            const scaleRatio = oldScale / this.canvasToImageScale; // new canvas size relative
            this.cropBox.x = Math.round(this.cropBox.x * scaleRatio);
            this.cropBox.y = Math.round(this.cropBox.y * scaleRatio);
            this.cropBox.width = Math.round(this.cropBox.width * scaleRatio);
            this.cropBox.height = Math.round(this.cropBox.height * scaleRatio);
            this.clampCropBox();
        }

        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = Math.floor(displayW * dpr);
        this.canvas.height = Math.floor(displayH * dpr);
        this.canvas.style.width = `${displayW}px`;
        this.canvas.style.height = `${displayH}px`;

        const ctx = this.ctx;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';

        // Draw image with rotation
        ctx.save();
        // Translate to canvas center in CSS pixels
        ctx.translate(displayW / 2, displayH / 2);
        ctx.rotate(rad);
        ctx.scale(fitScale, fitScale);
        // Draw source image centered
        ctx.drawImage(this.image, -srcW / 2, -srcH / 2, srcW, srcH);
        ctx.restore();

        // Draw darkened areas outside crop box
        const { x, y, width, height } = this.cropBox;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        if (y > 0) ctx.fillRect(0, 0, displayW, y);
        if (y + height < displayH) ctx.fillRect(0, y + height, displayW, displayH - (y + height));
        if (x > 0) ctx.fillRect(0, y, x, height);
        if (x + width < displayW) ctx.fillRect(x + width, y, displayW - (x + width), height);

        // Draw crop box border
        ctx.strokeStyle = '#0d6efd';
        ctx.lineWidth = 3;
        ctx.strokeRect(x, y, width, height);

        // Draw rule of thirds grid
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x + width / 3, y);
        ctx.lineTo(x + width / 3, y + height);
        ctx.moveTo(x + (width * 2) / 3, y);
        ctx.lineTo(x + (width * 2) / 3, y + height);
        ctx.moveTo(x, y + height / 3);
        ctx.lineTo(x + width, y + height / 3);
        ctx.moveTo(x, y + (height * 2) / 3);
        ctx.lineTo(x + width, y + (height * 2) / 3);
        ctx.stroke();

        // Draw corner handles
        const h = this.handleSize;
        const handles = [
            [x - h / 2, y - h / 2],
            [x + width - h / 2, y - h / 2],
            [x - h / 2, y + height - h / 2],
            [x + width - h / 2, y + height - h / 2],
        ];

        ctx.fillStyle = '#0d6efd';
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        for (const [hx, hy] of handles) {
            ctx.fillRect(hx, hy, h, h);
            ctx.strokeRect(hx, hy, h, h);
        }

        // Draw edge handles
        const edgeSize = 6;
        ctx.fillStyle = 'rgba(13, 110, 253, 0.8)';
        ctx.lineWidth = 1;
        const edges = [
            [x + width / 2 - edgeSize / 2, y - edgeSize / 2],
            [x + width / 2 - edgeSize / 2, y + height - edgeSize / 2],
            [x - edgeSize / 2, y + height / 2 - edgeSize / 2],
            [x + width - edgeSize / 2, y + height / 2 - edgeSize / 2],
        ];
        for (const [ex, ey] of edges) {
            ctx.fillRect(ex, ey, edgeSize, edgeSize);
            ctx.strokeRect(ex, ey, edgeSize, edgeSize);
        }
    }

    // --- Geometry helpers ---
    _rotatedDims(rad, w, h) {
        const absCos = Math.abs(Math.cos(rad));
        const absSin = Math.abs(Math.sin(rad));
        return { w: w * absCos + h * absSin, h: w * absSin + h * absCos };
    }

    _originalRectToRotatedBBox(rect, rad, srcW, srcH) {
        const { w: rotW, h: rotH } = this._rotatedDims(rad, srcW, srcH);
        const cx = srcW / 2;
        const cy = srcH / 2;
        const cos = Math.cos(rad);
        const sin = Math.sin(rad);

        const corners = [
            { x: rect.x, y: rect.y },
            { x: rect.x + rect.width, y: rect.y },
            { x: rect.x, y: rect.y + rect.height },
            { x: rect.x + rect.width, y: rect.y + rect.height },
        ].map((p) => {
            const dx = p.x - cx;
            const dy = p.y - cy;
            const rx = dx * cos - dy * sin;
            const ry = dx * sin + dy * cos;
            return { x: rx + rotW / 2, y: ry + rotH / 2 };
        });

        const minX = Math.min(...corners.map((p) => p.x));
        const minY = Math.min(...corners.map((p) => p.y));
        const maxX = Math.max(...corners.map((p) => p.x));
        const maxY = Math.max(...corners.map((p) => p.y));
        return { x: minX, y: minY, width: maxX - minX, height: maxY - minY };
    }

    _rotatedRectToOriginalBBox(rect, rad, srcW, srcH) {
        const { w: rotW, h: rotH } = this._rotatedDims(rad, srcW, srcH);
        const cx = srcW / 2;
        const cy = srcH / 2;
        const cos = Math.cos(rad);
        const sin = Math.sin(rad);

        const corners = [
            { x: rect.x, y: rect.y },
            { x: rect.x + rect.width, y: rect.y },
            { x: rect.x, y: rect.y + rect.height },
            { x: rect.x + rect.width, y: rect.y + rect.height },
        ].map((p) => {
            const x0 = p.x - rotW / 2;
            const y0 = p.y - rotH / 2;
            // inverse rotate by -rad
            const dx = x0 * cos + y0 * sin;
            const dy = -x0 * sin + y0 * cos;
            return { x: dx + cx, y: dy + cy };
        });

        const minX = Math.min(...corners.map((p) => p.x));
        const minY = Math.min(...corners.map((p) => p.y));
        const maxX = Math.max(...corners.map((p) => p.x));
        const maxY = Math.max(...corners.map((p) => p.y));
        // Clamp to original image bounds
        const x = Math.max(0, minX);
        const y = Math.max(0, minY);
        const w = Math.min(srcW, maxX) - x;
        const h = Math.min(srcH, maxY) - y;
        return { x, y, width: Math.max(0, w), height: Math.max(0, h) };
    }

    _clampRect(rect, bounds) {
        let { x, y, width, height } = rect;
        if (x < bounds.x) {
            width -= bounds.x - x;
            x = bounds.x;
        }
        if (y < bounds.y) {
            height -= bounds.y - y;
            y = bounds.y;
        }
        const maxW = bounds.x + bounds.width;
        const maxH = bounds.y + bounds.height;
        if (x + width > maxW) {
            width = maxW - x;
        }
        if (y + height > maxH) {
            height = maxH - y;
        }
        width = Math.max(1, width);
        height = Math.max(1, height);
        return { x, y, width, height };
    }

    _shrinkToRatio(rect, ratio, rotW, rotH) {
        const cx = rect.x + rect.width / 2;
        const cy = rect.y + rect.height / 2;
        let w = rect.width;
        let h = rect.height;
        // shrink to nearest ratio without expanding
        const w1 = h * ratio;
        const h1 = w / ratio;
        if (w1 <= w) {
            w = w1;
        } else {
            h = h1;
        }
        // ensure within bounds
        let x = cx - w / 2;
        let y = cy - h / 2;
        if (x < 0) x = 0;
        if (y < 0) y = 0;
        if (x + w > rotW) x = rotW - w;
        if (y + h > rotH) y = rotH - h;
        return { x, y, width: Math.max(1, w), height: Math.max(1, h) };
    }
}

// Export class for CommonJS consumers (e.g., Jest tests)
/* eslint-disable no-undef */
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = CropSelector;
}
