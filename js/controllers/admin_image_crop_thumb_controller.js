import { Controller } from "@hotwired/stimulus";

const MIN_CROP_SIZE = 20;

export default class extends Controller {
    static targets = [
        "container",
        "image",
        "overlay",
        "resizeHandle",
        "previewCanvas",
        "cropX",
        "cropY",
        "cropWidth",
        "cropHeight",
    ];

    connect() {
        if (
            !this.hasContainerTarget ||
            !this.hasImageTarget ||
            !this.hasOverlayTarget ||
            !this.hasPreviewCanvasTarget
        ) {
            return;
        }

        this.cropData = { x: 0, y: 0, width: 0, height: 0 };
        this.isDragging = false;
        this.isResizing = false;
        this.dragStart = { x: 0, y: 0 };
        this.imgNaturalWidth = 0;
        this.imgDisplayWidth = 0;
        this.imgDisplayHeight = 0;

        this.boundMouseDown = (event) => this.onMouseDown(event);
        this.boundMouseMove = (event) => this.onMouseMove(event);
        this.boundMouseUp = () => this.onMouseUp();
        this.boundImageLoad = () => this.initCrop();

        this.containerTarget.addEventListener("mousedown", this.boundMouseDown);
        document.addEventListener("mousemove", this.boundMouseMove);
        document.addEventListener("mouseup", this.boundMouseUp);
        this.imageTarget.addEventListener("load", this.boundImageLoad);

        if (this.imageTarget.complete) {
            this.initCrop();
        }

        // Backward compatibility for legacy pages/tests expecting a global helper.
        this.previousResetCrop = window.resetCrop;
        this.boundGlobalResetCrop = () => this.initCrop();
        window.resetCrop = this.boundGlobalResetCrop;
    }

    disconnect() {
        if (this.boundMouseDown) {
            this.containerTarget.removeEventListener(
                "mousedown",
                this.boundMouseDown,
            );
        }
        if (this.boundMouseMove) {
            document.removeEventListener("mousemove", this.boundMouseMove);
        }
        if (this.boundMouseUp) {
            document.removeEventListener("mouseup", this.boundMouseUp);
        }
        if (this.boundImageLoad) {
            this.imageTarget.removeEventListener("load", this.boundImageLoad);
        }

        if (window.resetCrop === this.boundGlobalResetCrop) {
            if (typeof this.previousResetCrop === "function") {
                window.resetCrop = this.previousResetCrop;
            } else {
                delete window.resetCrop;
            }
        }
    }

    reset(event) {
        event.preventDefault();
        this.initCrop();
    }

    initCrop() {
        const rect = this.imageTarget.getBoundingClientRect();
        if (!rect.width || !rect.height) {
            return;
        }

        this.imgDisplayWidth = rect.width;
        this.imgDisplayHeight = rect.height;
        this.imgNaturalWidth = this.imageTarget.naturalWidth || rect.width;

        const size = Math.min(this.imgDisplayWidth, this.imgDisplayHeight);
        this.cropData = {
            x: Math.floor((this.imgDisplayWidth - size) / 2),
            y: Math.floor((this.imgDisplayHeight - size) / 2),
            width: size,
            height: size,
        };

        this.updateOverlay();
    }

    onMouseDown(event) {
        const containerRect = this.containerTarget.getBoundingClientRect();
        const mouseX = event.clientX - containerRect.left;
        const mouseY = event.clientY - containerRect.top;

        if (
            this.hasResizeHandleTarget &&
            event.target === this.resizeHandleTarget
        ) {
            this.isResizing = true;
            this.dragStart = { x: mouseX, y: mouseY };
            return;
        }

        if (
            mouseX >= this.cropData.x &&
            mouseX <= this.cropData.x + this.cropData.width &&
            mouseY >= this.cropData.y &&
            mouseY <= this.cropData.y + this.cropData.height
        ) {
            this.isDragging = true;
            this.dragStart = {
                x: mouseX - this.cropData.x,
                y: mouseY - this.cropData.y,
            };
            return;
        }

        this.isDragging = true;
        this.cropData.x = mouseX;
        this.cropData.y = mouseY;
        this.cropData.width = 1;
        this.cropData.height = 1;
        this.dragStart = { x: mouseX, y: mouseY };
    }

    onMouseMove(event) {
        if (!this.isDragging && !this.isResizing) {
            return;
        }
        if (!document.body.contains(this.containerTarget)) {
            return;
        }

        event.preventDefault();

        const containerRect = this.containerTarget.getBoundingClientRect();
        const mouseX = event.clientX - containerRect.left;
        const mouseY = event.clientY - containerRect.top;

        if (this.isDragging && !this.isResizing) {
            if (this.cropData.width === 1 && this.cropData.height === 1) {
                const dx = mouseX - this.dragStart.x;
                const dy = mouseY - this.dragStart.y;
                const size = Math.max(
                    MIN_CROP_SIZE,
                    Math.min(Math.abs(dx), Math.abs(dy)),
                );

                this.cropData.x =
                    dx < 0 ? this.dragStart.x - size : this.dragStart.x;
                this.cropData.y =
                    dy < 0 ? this.dragStart.y - size : this.dragStart.y;

                this.cropData.x = Math.max(
                    0,
                    Math.min(this.cropData.x, this.imgDisplayWidth - size),
                );
                this.cropData.y = Math.max(
                    0,
                    Math.min(this.cropData.y, this.imgDisplayHeight - size),
                );
                this.cropData.width = size;
                this.cropData.height = size;
            } else {
                let newX = mouseX - this.dragStart.x;
                let newY = mouseY - this.dragStart.y;

                newX = Math.max(
                    0,
                    Math.min(newX, this.imgDisplayWidth - this.cropData.width),
                );
                newY = Math.max(
                    0,
                    Math.min(
                        newY,
                        this.imgDisplayHeight - this.cropData.height,
                    ),
                );

                this.cropData.x = newX;
                this.cropData.y = newY;
            }
        } else if (this.isResizing) {
            const deltaX = mouseX - this.cropData.x;
            const deltaY = mouseY - this.cropData.y;
            let newSize = Math.min(deltaX, deltaY);

            newSize = Math.max(MIN_CROP_SIZE, newSize);
            newSize = Math.min(
                newSize,
                this.imgDisplayWidth - this.cropData.x,
                this.imgDisplayHeight - this.cropData.y,
            );

            this.cropData.width = newSize;
            this.cropData.height = newSize;
        }

        this.updateOverlay();
    }

    onMouseUp() {
        this.isDragging = false;
        this.isResizing = false;
    }

    updateOverlay() {
        this.overlayTarget.style.left = `${this.cropData.x}px`;
        this.overlayTarget.style.top = `${this.cropData.y}px`;
        this.overlayTarget.style.width = `${this.cropData.width}px`;
        this.overlayTarget.style.height = `${this.cropData.height}px`;
        this.overlayTarget.style.display =
            this.cropData.width > 0 && this.cropData.height > 0
                ? "block"
                : "none";

        this.updatePreview();
        this.updateFormFields();
    }

    updatePreview() {
        const scale = this.getScale();
        const srcX = Math.round(this.cropData.x * scale);
        const srcY = Math.round(this.cropData.y * scale);
        const srcWidth = Math.round(this.cropData.width * scale);
        const srcHeight = Math.round(this.cropData.height * scale);

        if (srcWidth <= 0 || srcHeight <= 0 || !this.imageTarget.complete) {
            return;
        }

        const ctx = this.previewCanvasTarget.getContext("2d");
        if (!ctx) {
            return;
        }

        ctx.clearRect(0, 0, 150, 150);
        ctx.drawImage(
            this.imageTarget,
            srcX,
            srcY,
            srcWidth,
            srcHeight,
            0,
            0,
            150,
            150,
        );
    }

    updateFormFields() {
        const scale = this.getScale();

        if (this.hasCropXTarget) {
            this.cropXTarget.value = String(
                Math.round(this.cropData.x * scale),
            );
        }
        if (this.hasCropYTarget) {
            this.cropYTarget.value = String(
                Math.round(this.cropData.y * scale),
            );
        }
        if (this.hasCropWidthTarget) {
            this.cropWidthTarget.value = String(
                Math.round(this.cropData.width * scale),
            );
        }
        if (this.hasCropHeightTarget) {
            this.cropHeightTarget.value = String(
                Math.round(this.cropData.height * scale),
            );
        }
    }

    getScale() {
        if (!this.imgDisplayWidth) {
            return 1;
        }

        return this.imgNaturalWidth / this.imgDisplayWidth;
    }
}
