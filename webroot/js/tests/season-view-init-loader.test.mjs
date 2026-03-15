import { jest } from "@jest/globals";

describe("season-view-init-loader", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    test("registers turbo event handlers and handles frame load", async () => {
        const addSpy = jest.spyOn(document, "addEventListener");

        await import("../season-view-init-loader.mjs");

        const frameHandler = addSpy.mock.calls.find(
            (call) => call[0] === "turbo:frame-load",
        );
        expect(frameHandler).toBeDefined();

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        expect(() =>
            frameHandler[1]({ type: "turbo:frame-load", target: frame }),
        ).not.toThrow();

        addSpy.mockRestore();
    });

    test("registers turbo:load handler and handles load event", async () => {
        const addSpy = jest.spyOn(document, "addEventListener");

        await import("../season-view-init-loader.mjs");

        const loadHandler = addSpy.mock.calls.find(
            (call) => call[0] === "turbo:load",
        );
        expect(loadHandler).toBeDefined();

        expect(() =>
            loadHandler[1]({ type: "turbo:load", target: document }),
        ).not.toThrow();

        addSpy.mockRestore();
    });

    test("boots immediately when document already loaded", async () => {
        const originalDescriptor = Object.getOwnPropertyDescriptor(
            document,
            "readyState",
        );
        Object.defineProperty(document, "readyState", {
            configurable: true,
            get: () => "complete",
        });

        await expect(
            import("../season-view-init-loader.mjs"),
        ).resolves.toBeDefined();

        if (originalDescriptor) {
            Object.defineProperty(document, "readyState", originalDescriptor);
        }
    });

    test("turbo:frame-load uses document when target is not Element", async () => {
        const addSpy = jest.spyOn(document, "addEventListener");
        await import("../season-view-init-loader.mjs");

        const frameHandler = addSpy.mock.calls.find(
            (call) => call[0] === "turbo:frame-load",
        );
        expect(frameHandler).toBeDefined();

        expect(() =>
            frameHandler[1]({ type: "turbo:frame-load", target: {} }),
        ).not.toThrow();

        addSpy.mockRestore();
    });
});
