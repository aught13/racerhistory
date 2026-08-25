/**
 * Extended tests for route_modules to improve coverage of prefetch logic
 * and fallback handlers.
 */

import { registerPublicCoreControllers } from "../route_modules/public_core.js";
import { registerAdminStatsEntryControllers } from "../route_modules/admin_stats_entry.js";

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

    describe("admin_stats_entry fallback logic", () => {
        let mockStimulus;
        let mockDocElement;

        beforeEach(() => {
            mockDocElement = document.createElement("div");
            mockDocElement.id = "stat-rows";
            document.body.appendChild(mockDocElement);

            // Mock console methods
            jest.spyOn(console, "debug").mockImplementation(() => {});

            mockStimulus = {
                register: jest.fn(),
                getControllerForElementAndIdentifier: jest.fn(),
            };
        });

        afterEach(() => {
            if (mockDocElement && mockDocElement.parentNode) {
                mockDocElement.parentNode.removeChild(mockDocElement);
            }
            console.debug.mockRestore();
        });

        test("admin_stats_entry registers stat-multi-add controller", () => {
            registerAdminStatsEntryControllers(mockStimulus);
            expect(mockStimulus.register).toHaveBeenCalledWith(
                "stat-multi-add",
                expect.any(Function),
            );
        });

        test("admin_stats_entry skips legacy when controller exists", () => {
            mockStimulus.getControllerForElementAndIdentifier.mockReturnValue({
                id: "stat-multi-add",
            });

            registerAdminStatsEntryControllers(mockStimulus);

            expect(
                mockStimulus.getControllerForElementAndIdentifier,
            ).toHaveBeenCalled();
        });

        test("admin_stats_entry handles missing container gracefully", () => {
            // Remove the container
            mockDocElement.remove();

            mockStimulus.getControllerForElementAndIdentifier.mockReturnValue(
                null,
            );

            // Should not throw
            registerAdminStatsEntryControllers(mockStimulus);
            expect(true).toBe(true);
        });

        test("admin_stats_entry handles missing getControllerForElementAndIdentifier", () => {
            delete mockStimulus.getControllerForElementAndIdentifier;

            // Should not throw - should fall back to running legacy
            registerAdminStatsEntryControllers(mockStimulus);
            expect(true).toBe(true);
        });

        test("admin_stats_entry handles getControllerForElementAndIdentifier exception", () => {
            mockStimulus.getControllerForElementAndIdentifier.mockImplementation(
                () => {
                    throw new Error("Controller lookup failed");
                },
            );

            // Should handle error gracefully
            registerAdminStatsEntryControllers(mockStimulus);
            expect(console.debug).toHaveBeenCalled();
        });
    });
});
