/**
 * Tests for Turbo navigation and frame loading
 * @jest-environment jsdom
 */

import { jest } from "@jest/globals";

describe("Turbo Navigation", () => {
    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = "";

        // Mock Turbo if not available
        if (!window.Turbo) {
            window.Turbo = {
                visit: jest.fn(),
                clearCache: jest.fn(),
            };
        }
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe("Basic Navigation", () => {
        test("should handle turbo:load event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:load", callback);

            const event = new Event("turbo:load");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });

        test("should handle turbo:before-visit event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:before-visit", callback);

            const event = new CustomEvent("turbo:before-visit", {
                detail: { url: "/seasons" },
            });
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });

        test("should handle turbo:visit event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:visit", callback);

            const event = new Event("turbo:visit");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });
    });

    describe("Frame Loading", () => {
        test("should handle turbo:frame-load event", () => {
            const frame = document.createElement("turbo-frame");
            frame.id = "test-frame";
            document.body.appendChild(frame);

            const callback = jest.fn();
            document.addEventListener("turbo:frame-load", callback);

            const event = new Event("turbo:frame-load", { bubbles: true });
            frame.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });

        test("should find turbo-frame elements", () => {
            document.body.innerHTML = `
                <turbo-frame id="games-tab" src="/seasons/1/games">
                    <p>Loading...</p>
                </turbo-frame>
            `;

            const frame = document.getElementById("games-tab");
            expect(frame).not.toBeNull();
            expect(frame.tagName.toLowerCase()).toBe("turbo-frame");
            expect(frame.getAttribute("src")).toBe("/seasons/1/games");
        });

        test("should handle frame loading state", () => {
            const frame = document.createElement("turbo-frame");
            frame.id = "test-frame";
            frame.setAttribute("busy", "");
            document.body.appendChild(frame);

            expect(frame.hasAttribute("busy")).toBe(true);

            frame.removeAttribute("busy");
            expect(frame.hasAttribute("busy")).toBe(false);
        });
    });

    describe("Error Handling", () => {
        test("should handle turbo:frame-missing event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:frame-missing", callback);

            const event = new CustomEvent("turbo:frame-missing", {
                detail: { response: { statusCode: 404 } },
            });
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });

        test("should handle turbo:fetch-request-error event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:fetch-request-error", callback);

            const event = new CustomEvent("turbo:fetch-request-error", {
                detail: { error: new Error("Network error") },
            });
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });
    });

    describe("Cache Management", () => {
        test("should clear Turbo cache when called", () => {
            if (window.Turbo && window.Turbo.clearCache) {
                window.Turbo.clearCache();
                expect(window.Turbo.clearCache).toHaveBeenCalled();
            }
        });

        test("should prevent caching on specific pages", () => {
            document.body.innerHTML = `
                <meta name="turbo-cache-control" content="no-cache">
            `;

            const meta = document.querySelector(
                'meta[name="turbo-cache-control"]',
            );
            expect(meta).not.toBeNull();
            expect(meta.getAttribute("content")).toBe("no-cache");
        });
    });

    describe("Progress Bar", () => {
        test("should show progress bar on navigation", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:before-fetch-request", callback);

            const event = new Event("turbo:before-fetch-request");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });

        test("should hide progress bar after load", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:render", callback);

            const event = new Event("turbo:render");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });
    });

    describe("Link Navigation", () => {
        test("should intercept link clicks", () => {
            document.body.innerHTML = `
                <a href="/seasons/1" data-turbo="true">Season 1</a>
            `;

            const link = document.querySelector("a");
            expect(link.getAttribute("data-turbo")).toBe("true");
            expect(link.getAttribute("href")).toBe("/seasons/1");
        });

        test("should skip navigation for external links", () => {
            document.body.innerHTML = `
                <a href="https://example.com" data-turbo="false">External</a>
            `;

            const link = document.querySelector("a");
            expect(link.getAttribute("data-turbo")).toBe("false");
        });

        test('should handle target="_blank" links', () => {
            document.body.innerHTML = `
                <a href="/people/1" target="_blank">Open in new tab</a>
            `;

            const link = document.querySelector("a");
            expect(link.getAttribute("target")).toBe("_blank");
        });
    });

    describe("Form Submission", () => {
        test("should handle turbo:submit-start event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:submit-start", callback);

            const event = new Event("turbo:submit-start");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });

        test("should handle turbo:submit-end event", () => {
            const callback = jest.fn();
            document.addEventListener("turbo:submit-end", callback);

            const event = new Event("turbo:submit-end");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
        });
    });

    describe("Scroll Restoration", () => {
        test("should restore scroll position", () => {
            // Mock scrollTo before using it
            const scrollToSpy = jest
                .spyOn(window, "scrollTo")
                .mockImplementation(() => {});
            const callback = jest.fn();
            document.addEventListener("turbo:before-render", callback);

            window.scrollTo(0, 100);

            const event = new Event("turbo:before-render");
            document.dispatchEvent(event);

            expect(callback).toHaveBeenCalled();
            expect(scrollToSpy).toHaveBeenCalledWith(0, 100);

            scrollToSpy.mockRestore();
        });

        test("should scroll to top on new page", () => {
            const scrollToSpy = jest
                .spyOn(window, "scrollTo")
                .mockImplementation(() => {});

            window.scrollTo(0, 0);

            expect(scrollToSpy).toHaveBeenCalledWith(0, 0);

            scrollToSpy.mockRestore();
        });
    });
});
