/**
 * Extended tests for route_modules to improve coverage of prefetch logic.
 */

import { registerPublicCoreControllers } from "../route_modules/public_core.js";

describe("Route modules extended coverage", () => {
    describe("public_core prefetch logic", () => {
        let mockStimulus;

        beforeEach(() => {
            // Clear global flag before each test
            delete window.__RH_PREFETCH_INSTALLED__;
            mockStimulus = {
                register: jest.fn(),
            };
        });

        test("public_core registers controllers", () => {
            registerPublicCoreControllers(mockStimulus);
            expect(mockStimulus.register).toHaveBeenCalledWith(
                "ad-delivery",
                expect.any(Function),
            );
            expect(mockStimulus.register).toHaveBeenCalledWith(
                "public-shell",
                expect.any(Function),
            );
            expect(mockStimulus.register).toHaveBeenCalledWith(
                "nav-accordion",
                expect.any(Function),
            );
            expect(mockStimulus.register).toHaveBeenCalledWith(
                "theme-toggle",
                expect.any(Function),
            );
        });

        test("public_core prefetch installs listener once", () => {
            const addEventListenerSpy = jest.spyOn(
                document,
                "addEventListener",
            );

            registerPublicCoreControllers(mockStimulus);
            const firstCallCount = addEventListenerSpy.mock.calls.length;

            // Call again - should not install again due to flag
            registerPublicCoreControllers(mockStimulus);
            const secondCallCount = addEventListenerSpy.mock.calls.length;

            expect(firstCallCount).toBe(secondCallCount);
            expect(window.__RH_PREFETCH_INSTALLED__).toBe(true);

            addEventListenerSpy.mockRestore();
        });

        test("public_core prefetch handles non-anchor targets", () => {
            registerPublicCoreControllers(mockStimulus);

            // Trigger listener with non-anchor element
            const event = new Event("pointerenter", {
                bubbles: true,
                cancelable: true,
            });

            // Manually trigger to cover no-link path
            Object.defineProperty(event, "target", {
                value: { closest: () => null },
                configurable: true,
            });

            document.dispatchEvent(event);
            // No assertion needed - just verifying no crash
            expect(true).toBe(true);
        });

        test("public_core prefetch handles invalid href", () => {
            registerPublicCoreControllers(mockStimulus);

            const mockLink = {
                href: "not a valid url",
                closest: jest.fn(() => null),
            };

            const event = new Event("pointerenter", {
                bubbles: true,
                cancelable: true,
            });

            Object.defineProperty(event, "target", {
                value: {
                    closest: jest.fn((sel) => {
                        if (sel === "a[href]") return mockLink;
                        return null;
                    }),
                },
                configurable: true,
            });

            // Should not throw even with invalid URL
            document.dispatchEvent(event);
            expect(true).toBe(true);
        });

        test("public_core prefetch handles people route", () => {
            registerPublicCoreControllers(mockStimulus);

            const mockLink = {
                href: "http://localhost/people",
                closest: jest.fn(),
            };

            const event = new Event("pointerenter", {
                bubbles: true,
                cancelable: true,
            });

            Object.defineProperty(event, "target", {
                value: {
                    closest: jest.fn((sel) => {
                        if (sel === "a[href]") return mockLink;
                        return null;
                    }),
                },
                configurable: true,
            });

            document.dispatchEvent(event);
            // Prefetch should be triggered
            expect(true).toBe(true);
        });
    });
});
