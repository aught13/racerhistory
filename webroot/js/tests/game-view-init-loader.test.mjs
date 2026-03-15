import { jest } from "@jest/globals";

describe("game-view-init-loader", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    test("registers turbo event handlers and handles frame load", async () => {
        const addSpy = jest.spyOn(document, "addEventListener");

        await import("../game-view-init-loader.mjs");

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

        await import("../game-view-init-loader.mjs");

        const loadHandler = addSpy.mock.calls.find(
            (call) => call[0] === "turbo:load",
        );
        expect(loadHandler).toBeDefined();

        expect(() =>
            loadHandler[1]({ type: "turbo:load", target: document }),
        ).not.toThrow();

        addSpy.mockRestore();
    });
});
